<?php

namespace Crater\Mail;

use Crater\Models\CompanySetting;
use Crater\Models\EmailLog;
use Crater\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Vinkla\Hashids\Facades\Hashids;

class SendEstimateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $data = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $log = EmailLog::create([
            'from' => $this->data['from'],
            'to' => $this->data['to'],
            'subject' => $this->data['subject'],
            'body' => $this->data['body'],
            'mailable_type' => Estimate::class,
            'mailable_id' => $this->data['estimate']['id'],
        ]);

        $log->token = Hashids::connection(EmailLog::class)->encode($log->id);
        $log->save();

        $this->data['url'] = route('estimate', ['email_log' => $log->token]).'?pdf=1';

        $locale = CompanySetting::getSetting('language', $this->data['estimate']['company_id']);
        $this->data['view_label'] = __('estimates.view_estimate', [], $locale ?: 'en');

        $mailContent = $this->from($this->data['from'], config('mail.from.name'))
                    ->subject($this->data['subject'])
                    ->markdown('emails.send.estimate', ['data', $this->data]);

        if ($this->data['attach']['data']) {
            $mailContent->attachData(
                $this->data['attach']['data']->output(),
                $this->data['estimate']['estimate_number'].'.pdf'
            );
        }

        return $mailContent;
    }
}
