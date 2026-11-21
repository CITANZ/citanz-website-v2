<?php

namespace App\Web\Email;

use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\Ses\SesClient;
use Swift_DependencyContainer;
use Swift_Events_EventDispatcher;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Swift_TransportException;

/**
 * Sends mail through the Amazon SES API (SendRawEmail) instead of SMTP.
 *
 * The SES client gets its credentials from the AWS SDK's default chain, so on
 * EC2 it uses the instance role and no mail password is stored anywhere.
 * (The SES SMTP interface only accepts long-term IAM user keys.)
 *
 * Swift plugins still run, so SS_SEND_ALL_EMAILS_TO / _FROM keep working.
 */
class SesApiTransport implements Swift_Transport
{
    /** @var SesClient */
    private $client;

    /** @var Swift_Events_EventDispatcher */
    private $eventDispatcher;

    public function __construct(SesClient $client, Swift_Events_EventDispatcher $eventDispatcher = null)
    {
        $this->client = $client;
        $this->eventDispatcher = $eventDispatcher
            ?: Swift_DependencyContainer::getInstance()->lookup('transport.eventdispatcher');
    }

    public function isStarted()
    {
        return true;
    }

    public function start()
    {
    }

    public function stop()
    {
    }

    public function ping()
    {
        return true;
    }

    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param string[] $failedRecipients
     * @return int number of recipients the message was handed over for
     * @throws Swift_TransportException when SES rejects the message or no credentials are available
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $failedRecipients = (array) $failedRecipients;

        $evt = $this->eventDispatcher->createSendEvent($this, $message);
        if ($evt) {
            $this->eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        // Read recipients after the plugins ran (SS_SEND_ALL_EMAILS_TO may have changed them).
        $recipients = array_keys(array_merge(
            (array) $message->getTo(),
            (array) $message->getCc(),
            (array) $message->getBcc()
        ));

        // Bcc goes in Destinations only, never in the headers other people receive.
        $bcc = $message->getBcc();
        $message->setBcc([]);
        $raw = $message->toString();
        $message->setBcc($bcc);

        try {
            $this->client->sendRawEmail([
                'Destinations' => $recipients,
                'RawMessage' => ['Data' => $raw],
            ]);
        } catch (AwsException | CredentialsException $e) {
            // CredentialsException: no instance role (or IMDS unreachable from the container).
            $failedRecipients = array_merge($failedRecipients, $recipients);
            if ($evt) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_FAILED);
                $evt->setFailedRecipients($recipients);
                $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
            }
            $reason = $e instanceof AwsException && $e->getAwsErrorMessage()
                ? $e->getAwsErrorMessage()
                : $e->getMessage();
            throw new Swift_TransportException('Could not send through Amazon SES: ' . $reason, 0, $e);
        }

        if ($evt) {
            $evt->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
            $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        return count($recipients);
    }
}
