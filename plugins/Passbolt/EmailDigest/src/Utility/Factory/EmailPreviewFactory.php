<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.13.0
 */

namespace Passbolt\EmailDigest\Utility\Factory;

use Cake\Mailer\Email;
use Cake\ORM\Entity;
use Passbolt\EmailDigest\Utility\Mailer\EmailDigestInterface;
use Passbolt\EmailDigest\Utility\Mailer\EmailPreview;

/**
 * Create email previews from an email entity or from an email digest.
 *
 * It allow to render an email with or without a layout in what is called an EmailPreview.
 * The email is rendered exactly as it would be send.
 * The produced EmailPreview also contains the headers as it would have been sent.
 *
 * @see EmailPreview
 *
 * It can:
 * - create previews from emailqueue entities which are used to create digests.
 * - create previews from EmailDigests which are used to preview the email before it is send.
 */
class EmailPreviewFactory
{
    /**
     * Create a snapshot of the email as it would be rendered from an email digest.
     *
     * @param \Passbolt\EmailDigest\Utility\Mailer\EmailDigestInterface $emailDigest Email digest to get a snapshot of
     * @param string|false|null $layout Layout file name to set.
     * @return \Passbolt\EmailDigest\Utility\Mailer\EmailPreview
     */
    public function renderEmailPreviewFromDigest(EmailDigestInterface $emailDigest, $layout = false)
    {
        $email = $this->mapEmailDigestToMailerEmail(new Email('default'), $emailDigest);

        $this->configureEmailView($email, $emailDigest->getTemplate(), $layout);

        return $this->renderEmailContent($email);
    }

    /**
     * Create a snapshot of the email as it would be rendered from an email.
     *
     * @param \Cake\ORM\Entity $emailData Email data to get a snapshot of
     * @param string|false|null $layout Layout file name to set.
     * @return \Passbolt\EmailDigest\Utility\Mailer\EmailPreview
     */
    public function renderEmailPreviewFromEmailEntity(Entity $emailData, $layout = false)
    {
        $configName = $emailData->config;
        $theme = empty($emailData->theme) ? '' : (string)$emailData->theme;

        $email = $this->mapEmailEntityToMailerEmail(new Email($configName), $emailData);

        $this->configureEmailView($email, $emailData->template, $layout, $theme);

        return $this->renderEmailContent($email);
    }

    /**
     * Return a preview of the email as it was sent with its headers and its content.
     * The Email::send() method handle the render of the email. It call the send method of its configured
     * transport once its render is done which can be retrieved through Email::getContent() method.
     * The trick is to attach a Debug transport which do not send the email but which gives us instead an
     * opportunity to retrieve the content and the headers of the email as it would be send.
     *
     * @param \Cake\Mailer\Email $email A Mailer email
     * @return \Passbolt\EmailDigest\Utility\Mailer\EmailPreview
     * @see Email::send()
     * @see DebugTransport::send()
     */
    private function renderEmailContent(Email $email)
    {
        $email->setTransport('Debug');

        $contents = $email->send();

        return new EmailPreview($contents['headers'], $contents['message']);
    }

    /**
     * Configure the email view for a Mailer email, theme, template, layout can be changed.
     *
     * @param \Cake\Mailer\Email $email An Email
     * @param string $template Template
     * @param string|false|null $layout Layout file name to set.
     * @param string|false|null $theme Theme name.
     * @return void
     */
    private function configureEmailView(Email $email, string $template, $layout = null, $theme = null)
    {
        $email->viewBuilder()
            ->setVar('title', 'Email digest preview')
            ->setLayout($layout)
            ->setTheme($theme)
            ->setTemplate($template);
    }

    /**
     * Map an instance of EmailDigest to an instance of Email, so it can be send.
     *
     * @param \Cake\Mailer\Email $email An instance of Email
     * @param \Passbolt\EmailDigest\Utility\Mailer\EmailDigestInterface $emailDigest An instance of EmailDigest
     * @return \Cake\Mailer\Email
     */
    private function mapEmailDigestToMailerEmail(Email $email, EmailDigestInterface $emailDigest)
    {
        $email
            ->setTo($emailDigest->getEmailRecipient())
            ->setSubject($emailDigest->getSubject())
            ->setEmailFormat($emailDigest->getEmailFormat())
            ->setMessageId(false)
            ->setViewVars($emailDigest->getViewVars())
            ->setReturnPath($email->getFrom());

        return $email;
    }

    /**
     * Map an instance of Emailqueue email to an instance of Email, so it can be send.
     *
     * @param \Cake\Mailer\Email $email An instance of Email
     * @param \Cake\ORM\Entity $emailData An instance of Emailqueue email
     * @return \Cake\Mailer\Email
     * @see \EmailQueue\Model\Table\EmailQueueTable
     */
    private function mapEmailEntityToMailerEmail(Email $email, Entity $emailData)
    {
        $headers = empty($emailData->headers) ? [] : (array)$emailData->headers;
        $viewVars = empty($emailData->template_vars) ? [] : $emailData->template_vars;
        $emailDataErrorMessage = null;

        if (!empty($emailData->from_email) && !empty($emailData->from_name)) {
            $email->setFrom($emailData->from_email, $emailData->from_name);
        }

        $email
            ->setTo($emailData->email)
            ->setSubject($emailData->subject)
            ->setEmailFormat($emailData->format)
            ->addHeaders($headers)
            ->setViewVars($viewVars)
            ->setMessageId(false)
            ->setReturnPath($email->getFrom());

        return $email;
    }
}
