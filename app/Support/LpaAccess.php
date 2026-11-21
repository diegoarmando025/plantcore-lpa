<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
class LpaAccess {
 public static function role(int $userId,int $plantId):?string {
  return DB::table('plant_user')->where('user_id',$userId)->where('plant_id',$plantId)->value('role');
 }
 public static function enforce(int $userId,int $plantId,string $action):void {
  $role=self::role($userId,$plantId);
  abort_if($role===null,404);
  $allowed=match($action){'read'=>['viewer','auditor','supervisor'], 'audit'=>['auditor','supervisor'], 'manage'=>['supervisor'],default=>[]};
  abort_unless(in_array($role,$allowed,true),403);
 }
}
