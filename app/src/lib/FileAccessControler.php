<?php

namespace Leochenftw\Controller;
use SilverStripe\Control\HTTPRequest;
use PageController;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class FileAccessControler extends PageController
{
    public function index(HTTPRequest $request)
    {
        if ($fid = $request->param('id')) {
            if ($file = Versioned::get_by_stage(File::class, 'Stage')->byID($fid)) {
                if ($file->ClassName != Folder::class) {
                    if ($file->isPublished()) {
                        return $this->redirect($file->getAbsoluteURL());
                    }

                    if ($member = Member::currentUser()) {
                        return $this->redirect($file->getURL());
                    }

                    return $this->httpError(403, 'Permission Denied');
                }

                return $this->httpError(400, 'Nothing to display');
            }
        }

        return $this->httpError(404, 'Not found');
    }
}
