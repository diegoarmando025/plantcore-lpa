<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
class CorrectiveAction extends Model {
 protected $fillable=['audit_answer_id','owner_id','description','status','due_date','closed_at'];
 protected function casts():array{return ['due_date'=>'date','closed_at'=>'datetime'];}
 public function evidences():\Illuminate\Database\Eloquent\Relations\HasMany{return $this->hasMany(CorrectiveActionEvidence::class,'corrective_action_id');}
 public function owner():BelongsTo{return $this->belongsTo(User::class,'owner_id');}
 public function answer():BelongsTo{return $this->belongsTo(AuditAnswer::class,'audit_answer_id');}
 public function answerAudit():HasOneThrough{return $this->hasOneThrough(Audit::class,AuditAnswer::class,'id','id','audit_answer_id','audit_id');}
}
