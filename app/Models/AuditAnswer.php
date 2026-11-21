<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
class AuditAnswer extends Model {
 protected $fillable=['audit_id','audit_question_id','result','note'];
 public function action():HasOne{return $this->hasOne(CorrectiveAction::class,'audit_answer_id');}
}
