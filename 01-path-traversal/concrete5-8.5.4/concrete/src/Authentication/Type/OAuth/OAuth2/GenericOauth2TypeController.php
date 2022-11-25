<?php
namespace Concrete\Core\Authentication\Type\OAuth\OAuth2;

use Concrete\Core\Authentication\LoginException;
use Concrete\Core\Authentication\Type\OAuth\GenericOauthTypeController;
use Concrete\Core\Routing\RedirectResponse;
use OAuth\Common\Exception\Exception;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\OAuth2\Service\AbstractService;
use Concrete\Core\User\User;

abstract class GenericOauth2TypeController extends GenericOauthTypeController
{
    /** @var AbstractService */
    protected $service;

    public function handle_authentication_attempt()
    {
        $url = $this->getService()->getAuthorizationUri($this->getAdditionalRequestParameters());

        id(new RedirectResponse((string) $url))->send();
        exit;
    }

    public function handle_authentication_callback()
    {
        $user = $this->app->make(User::class);
        if ($user && !$user->isError() && $user->isLoggedIn()) {
            $this->handle_attach_callback();
        }

        try {
            $code = \Request::getInstance()->get('code');
            $token = $this->getService()->requestAccessToken($code);
            $this->setToken($token);
        } catch (TokenResponseException $e) {
            $this->showError(t('Failed authentication: %s', $e->getMessage()));
            exit;
        }

        if ($token) {
            try {
                $user = $this->attemptAuthentication();
                if ($user) {
                    return $this->completeAuthentication($user);
                } else {
                    $this->showError(
                        t('No local user account associated with this user, please log in with a local account and connect your account from your user profile.'));
                }
            } catch (LoginException $e) {
                $this->showError($e->getMessage());
            } catch (Exception $e) {
                $this->showError($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
                $this->showError(t('An unexpected error occurred.'));
            }
        } else {
            $this->showError(t('Failed to complete authentication.'));
        }
        exit;
    }

    public function handle_attach_attempt()
    {
        $url = $this->getService()->getAuthorizationUri($this->getAdditionalRequestParameters());

        id(new RedirectResponse((string) $url))->send();
        exit;
    }

    public function handle_attach_callback()
    {
        $user = $this->app->make(User::class);
        if (!$user->isRegistered()) {
            id(new RedirectResponse(\URL::to('')))->send();
            exit;
        }

        try {
            $code = \Request::getInstance()->get('code');
            $token = $this->getService()->requestAccessToken($code);
        } catch (TokenResponseException $e) {
            $this->showError(t('Failed authentication: %s', $e->getMessage()));
            exit;
        }

        if ($token) {
            if ($this->bindUser($user, $this->getExtractor(true)->getUniqueId())) {
                $this->showSuccess(t('Successfully attached.'));
                exit;
            }
        }
        $this->showError(t('Unable to attach user.'));
        exit;
    }

    /**
     * @return \OAuth\OAuth2\Service\AbstractService
     */
    public function getService()
    {
        return parent::getService();
    }

    public function view()
    {
        // Nothing here.
    }

    /**
     * Method used to clean up.
     * This method must be defined, if it isn't needed, leave it blank.
     *
     * @param \User $u
     */
    public function deauthenticate(User $u)
    {
        // Nothing to do here.
    }

    /**
     * Test user authentication status.
     *
     * @param \User $u
     *
     * @return bool Returns true if user is authenticated, false if not
     */
    public function isAuthenticated(User $u)
    {
        return $u->isLoggedIn();
    }
}
