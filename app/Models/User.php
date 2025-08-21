<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'T_USUARIOS';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'documento',
        'nombres',
        'telefono',
        'numeroInvitados',
        'invitados',
        'imgInvitacion',
        'qrImagen',
        'checkIn',
    ];
}
