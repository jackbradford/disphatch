<?php
/**
 * @file Activation.php
 * This file provides a class to represent an Activation record.
 *
 */
namespace JackBradford\Disphatch\Etc;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\MailerException;

/**
 * @class Activation
 * This class provides a means of interacting with Activation records 
 * independent of the third-party user management library. That is, if that
 * library is ever replaced with a different one, a new implementation of this
 * class can be added, as opposed to finding every place in the codebase where
 * the first library was called and changing the implementation in each of
 * those places.
 *
 */
class Activation {

    private $code;
    private $userId;
    private $createdAt;
    private $updatedAt;
    private $id;

    /**
     * @method Activation::__construct
     * Create an instance of the Activation class, which represnets a User
     * Activation record.
     *
     * @param array $data
     *  ['code']        The activation code.
     *  ['userId']      The ID of the user being activated.
     *  ['createdAt']   The time of the activation record's creation.
     *  ['updatedAt']   The time of the activation record's last update.
     *  ['id']          The ID of the activation record.
     *
     * @return Activation
     */
    public function __construct(array $data) {

        if (!$this->validateConstructorInput($data)) {

            throw new \Exception(__METHOD__ . ': Missings argument(s).');
        }

        $this->code = $data['code'];
        $this->userId = $data['userId'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->id = $data['id'];
    }

    /**
     * @method Activation::getDetails
     * Get the Activation record's data.
     *
     * @return obj
     */
    public function getDetails() {

        return (object) [

            'code' => $this->code,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'id' => $this->id,
        ];
    }

    /**
     * @method Activation::sendActivationEmail()
     * Send an activation link to the provided user email.
     *
     * @param object $server
     * An object containing the server settings.
     * ->host Specify main and backup SMTP servers.
     *      e.g. 'smtp1.example.com;smtp2.example.com'
     * ->username SMTP username.
     * ->password SMTP password.
     * ->port TCP port to connect to.
     * ->fromAddress From address.
     * ->fromName The name for the From address.
     * ->recipients (array) An array of objects:
     *      [ 'address' => '', 'name' => '' ]
     * ->replyTo (optional) (object) 
     *      [ 'address' => '', 'name' => '' ]
     * ->cc (optional) (array) An array of strings.
     * ->bcc (optional) (array) An array of strings.
     * ->dkim_domain (string) The DKIM domain.
     * ->dkim_private (string) The full path to the DKIM private key.
     * ->dkim_selector (string) The DKIM selector.
     * ->dkim_passphrase (string)
     * ->dkim_identity (string)
     * @return void
     * Throws an exception if the email can't be sent.
     */
    public function sendActivationEmail($subject, $body,  $server) {

        $link = "/activate/" . $this->userId . '/' . $this->code;
        $altBody = '';
        $mail = new PHPMailer(true);

        try {

            // Server settings
            $mail->isSMTP();
            $mail->Host = $server->host;
            $mail->SMTPAuth = true;
            $mail->Username = $server->username;
            $mail->Password = $server->password;
            $mail->SMTPSecure = $server->smtp_secure;
            $mail->Port = $server->port;

            // Recipients
            $mail->setFrom($server->fromAddress, $server->fromName);
            foreach ($server->recipients as $recip) {

                if (!isset($recip->name)) $recip->name = '';
                $mail->addAddress($recip->address, $recip->name);
            }

            if (isset($server->replyTo)) {
                if (!isset($server->replyTo->name)) $server->replyTo->name = '';
                $mail->addReplyTo(
                    $server->replyTo->address,
                    $server->replyTo->name
                );
            }
            if (isset($server->cc)) {
                foreach ($server->cc as $cc) {
                    $mail->addCC($cc);
                }
            }
            if (isset($server->bcc)) {
                foreach ($server->bcc as $bcc) {
                    $mail->addBCC($bcc);
                }
            }

            // DKIM
            $mail->DKIM_domain = $server->dkim_domain;
            $mail->DKIM_private = $server->dkim_private;
            $mail->DKIM_selector = $server->dkim_selector;
            $mail->DKIM_passphrase = $server->dkim_passphrase;
            $mail->DKIM_identity = $mail->From;


            // Content
            $mail->isHTML(true);
            $mail->WordWrap = 72;
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody;
            $mail->send();
        }
        catch (MailerException $mailEx) {

            error_log('Could not send mail.');
            error_log($mailEx->errorMessage());
            throw new \Exception($mailEx->errorMessage());
        }
    }

    private function validateConstructorInput(array $data) {

        if (!isset($data['code'])) return false;
        if (!isset($data['userId'])) return false;
        if (!isset($data['createdAt'])) return false;
        if (!isset($data['updatedAt'])) return false;
        if (!isset($data['id'])) return false;
        return true;
    }
}

