<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditQuestion extends Model { public $timestamps=false; protected $fillable=['audit_template_id','text','sort_order']; }
