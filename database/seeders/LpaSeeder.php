<?php
namespace Database\Seeders;
use App\Models\AuditTemplate;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class LpaSeeder extends Seeder {
 public function run():void {
  abort_unless(app()->environment(['local','testing']),403);
  $plant=Plant::firstOrCreate(['code'=>'QRO-01'],['name'=>'Planta Querétaro']);
  $user=User::firstOrCreate(['email'=>'supervisor@lpa.example.invalid'],['name'=>'Supervisor LPA','password'=>Str::random(56)]);
  DB::table('plant_user')->updateOrInsert(['plant_id'=>$plant->id,'user_id'=>$user->id],['role'=>'supervisor']);
  $tpl=AuditTemplate::firstOrCreate(['plant_id'=>$plant->id,'name'=>'Auditoría de proceso'],['area'=>'Producción','active'=>true]);
  foreach(['¿Se utiliza la instrucción de trabajo vigente?','¿El material está correctamente identificado?','¿Se registran las verificaciones de calidad?','¿El área está limpia y ordenada?','¿El equipo cuenta con resguardos de seguridad?'] as $i=>$question){
   DB::table('audit_questions')->updateOrInsert(['audit_template_id'=>$tpl->id,'sort_order'=>$i+1],['text'=>$question]);
  }
 }
}
