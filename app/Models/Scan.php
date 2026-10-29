<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected $fillable = ['client_id', 'code', 'scanned_at', 'method', 'note'];
}
