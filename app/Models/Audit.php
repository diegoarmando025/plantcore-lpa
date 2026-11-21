<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Audit extends Model {
 protected $fillable=['plant_id','audit_template_id','assigned_to','created_by','scheduled_for','status','completed_at'];
 protected function casts():array{return ['scheduled_for'=>'date','completed_at'=>'datetime'];}
 public function template():BelongsTo{return $this->belongsTo(AuditTemplate::class,'audit_template_id');}
 public function assignee():BelongsTo{return $this->belongsTo(User::class,'assigned_to');}
 public function answers():HasMany{return $this->hasMany(AuditAnswer::class);}
}
