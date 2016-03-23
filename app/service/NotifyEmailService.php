<?php
namespace App\Service;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Utils\DateTime;

/**
 * User: Roman
 * Date: 23.03.2016
 * Time: 13:34
 */
class NotifyEmailService extends BaseService {

    /**
     * @param string $email
     * @return mixed
     */
    public function addNotifyEmail($email) {
        $row = $this->database
            ->table("notify_email")
            ->insert(array(
                'email' => $email,
                'sent' => 'N',
                'added' => new DateTime()
            ))->getPrimary(true);
        return $row;
    }

    /**
     * @param int $notify_email_id
     * @param int $download_import_id
     */
    public function addNotifyEmailDownloadImport($notify_email_id,$download_import_id) {
        $this->database->table("notify_email_download_import")->insert(array(
            'id_download_import' => $download_import_id,
            'id_notify_email' => $notify_email_id
        ));
    }

    /**
     * @param int $notify_email_id
     * @param int $wigle_aps_id
     */
    public function addNotifyEmailWigleAps($notify_email_id,$wigle_aps_id) {
        $this->database->table("notify_email_wigle_aps")->insert(array(
            'id_wigle_aps' => $wigle_aps_id,
            'id_notify_email' => $notify_email_id
        ));
    }

    /**
     * @param int $id
     * @return FALSE|mixed
     */
    public function getNotDownloadedCountByNotifyEmailId($id) {
        return $this->database->query("SELECT
                                        (SELECT COUNT(*) FROM notify_email ne
                                        LEFT JOIN notify_email_wigle_aps newa ON newa.id_notify_email = ne.id
                                        LEFT JOIN wigle_aps wa ON wa.id = newa.id_wigle_aps
                                        WHERE ne.id = " . intval($id) . " AND (wa.downloaded = 0))
                                      +(SELECT COUNT(*) FROM notify_email ne
                                        LEFT JOIN notify_email_download_import nedi ON nedi.id_notify_email = ne.id
                                        LEFT JOIN download_import di ON di.id = nedi.id_download_import
                                        WHERE ne.id = " . intval($id) . " AND (di.state < 4))
                                      AS pocet")->fetch();
    }

    /**
     * @return array|\Nette\Database\Table\IRow[]
     */
    public function getAllNotSentNotifyEmails() {
        return $this->database->table("notify_email")->where("sent","N")->fetchAll();
    }

    /**
     * @param int $id
     */
    public function markAsSent($id) {
        $this->database->table("notify_email")->where("id",$id)->update(array("sent"=>"Y"));
    }

    /**
     * @param int $id
     * @return bool|mixed|\Nette\Database\Table\IRow
     */
    public function getNotifyEmailById($id) {
        return $this->database->table("notify_email")->where("id",$id)->fetch();
    }

    /**
     * @param int $id
     */
    public function notifyByEmail($id) {
        $ne = $this->getNotifyEmailById($id);
        $mail = new Message();
        $mail->setFrom('Wifimapa <info@wifimapa.cz>')
            ->addTo($ne["email"])
            ->setSubject('Potvrzení získání dat')
            ->setBody("Dobrý den,\ndata o která jste požádal byla získána do databáze.");
        $mailer = new SendmailMailer();
        $mailer->send($mail);

    }



}