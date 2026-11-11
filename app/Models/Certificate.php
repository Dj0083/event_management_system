<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = ['event_id', 'registration_id', 'template_id', 'certificate_number', 'type', 'file_path'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }
}
