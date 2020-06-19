<?php


namespace app\index\tool;


class MailHelper
{
    private $emails = [];
    private $transport = null;
    private $message = null;

    public function __construct($email)
    {
        if (is_array($email)) {
            $this->emails = array_merge($this->emails, $email);
        } else if (is_string($email)) {
            $this->emails[] = $email;
        }
        $this->transport = (new \Swift_SmtpTransport(env('MAIL_HOST'), env('MAIL_PORT')))
            ->setUsername(env('MAIL_USERNAME'))
            ->setPassword(env('MAIL_PASSWORD'));
        if (strlen(env('MAIL_ENCRYPTION', ''))) {
            $this->transport->setEncryption(env('MAIL_ENCRYPTION'));
        }
    }

    public function exceptionMessage(string $title, \Exception $exception)
    {
        $this->message = (new \Swift_Message($title))
            ->setFrom([env('MAIL_FROM_ADDRESS')])
            ->setTo($this->emails)
            ->setBody("<h1>{$title}</h1><br><br>错误消息：<br>
                <h2>{$exception->getMessage()}</h2>
                <br><br>错误文件：<br>
                <h2>{$exception->getFile()}</h2>
                <br><br>错误代码行数：<br>
                <h2>{$exception->getLine()}</h2>
                <br><br>错误堆栈：<br>
                <pre>{$exception->getTraceAsString()}</pre>", 'text/html', 'utf-8');
    }

    public function send()
    {
        (new \Swift_Mailer($this->transport))->send($this->message);
    }

    public function cashOutMessage(string $title, $options)
    {
        $this->message = (new \Swift_Message($title))
            ->setFrom([env('MAIL_FROM_ADDRESS')])
            ->setTo($this->emails)
            ->setBody("<h1>{$title}</h1><br><br>品牌馆ID：
                <h2>{$options['shop_id']}</h2><br>
                <br><br>品牌馆名称：
                <h2>{$options['shop_name']}</h2>
                <br><br>提现金额：
                <h2>{$options['cash_price']}</h2>
                <br><br>申请时间：
                <h2>{$options['create_time']}</h2>
                <br><br>host：
                <h2>{$options['host']}</h2>
                <pre></pre>", 'text/html', 'utf-8');
    }
}
