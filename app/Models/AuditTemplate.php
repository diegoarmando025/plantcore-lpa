<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AuditTemplate extends Model {
 protected $fillable=['plant_id','name','area','active','level'];
 public function audits(): HasMany { return $this->hasMany(Audit::class); }
 public function questions(): HasMany { return $this->hasMany(AuditQuestion::class)->orderBy('sort_order')->orderBy('id'); }
}
