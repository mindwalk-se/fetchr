<?php
namespace Mindwalk;

class DocumentFetcher
{
    protected $repositories;
    protected $database;
    protected $mailConfig;

    public function __construct(array $repositories, DocumentFetchDatabase $database, array $mailConfig)
    {
        $this->repositories = $repositories;
        $this->database = $database;
        $this->mailConfig = $mailConfig;
    }

    public function fetch($dryRun = false)
    {
        $mailData = [];
        $temporaryFilenames = [];

        foreach ($this->repositories as $repository) {
            $repositoryObject = new $repository['repository'];
            $filenames = $repositoryObject->get($repository);

            if (!is_array($filenames)) {
                $filenames = [ $filenames ];
            }

            foreach ($filenames as $filename) {
                $hash = $this->getHash($repository, $filename);

                if ($this->database->exists($hash)) {
                    continue;
                }

                $this->database->store($hash);

                if (empty($repository['recipients'])) {
                    continue;
                }

                $temporaryFilenames[] = $filename;

                foreach ($repository['recipients'] as $recipient) {
                    if (!isset($mailData[$recipient])) {
                        $mailData[$recipient] = [];
                    }

                    $mailData[$recipient][] = $filename;
                }
            }
        }

        foreach ($mailData as $recipient => $filenames) {
            $mail = $this->getMail();
            $mail->addAddress($recipient);

            foreach ($filenames as $filename) {
                $mail->addAttachment($filename);
            }

            if ($dryRun) {
                echo 'Sending files '
                    . implode(', ', $filenames)
                    . " to $recipient"
                    . PHP_EOL;
                continue;
            }

            if (!$mail->send()) {
                throw new \Exception($mail->ErrorInfo);
            }
        }

        foreach ($temporaryFilenames as $temporaryFilename) {
            unlink($temporaryFilename);
        }
    }

    private function getHash(array $repository, $filename)
    {
        return sha1(serialize($repository) . md5_file($filename));
    }

    private function getMail()
    {
        $mail = new \PHPMailer;
        $mail->isSMTP();
        $mail->Host = $this->mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->mailConfig['username'];
        $mail->Password = $this->mailConfig['password'];
        $mail->SMTPSecure = $this->mailConfig['encryption'];
        $mail->Port = $this->mailConfig['port'];

        $mail->setFrom(
            $this->mailConfig['from']['address'],
                $this->mailConfig['from']['name']
        );

        $mail->Subject = 'New documents ' . date('Y-m-d');
        $mail->Body = 'I have attached very important documents for you great master!';

        return $mail;
    }
}