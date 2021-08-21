## OAuth Setup
- Generate oauth encryption key and set `OAUTH_ENCRYPTION_KEY` in .env file
    - `php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'`
- Create new OAuth Client in the CMS backend (/admin > OAuth Clients)
    - Add a Secret and keep a note of it
- Add client ID and Client Secret to `../frontend/.env.local`
    - Add `VUE_APP_OAUTH_CLIENT_ID="client id"` and `VUE_APP_OAUTH_CLIENT_SECRET="client secret"`

### OAuth certificates

There are pre-generated certificates that are used for the OAuth server the can be found in `app/certs` the are directories
for every environment (dev, test, live).

When setting up the project run the following commands to set the correct permissions on the certs.

    chown [user]:[webserver-user] app/certs/[env]/private.key
    chown [user]:[webserver-user] app/certs/[env]/public.key

    chmod 660 app/certs/[env]/private.key
    chmod 660 app/certs/[env]/public.key

If the permissions aren't set correctly you will get 500 errors from the auth api.

#### How to generate certificates

Run the following commands to generate certificates.

    openssl genrsa -out private.key 4096
    openssl rsa -in private.key -pubout -out public.key
