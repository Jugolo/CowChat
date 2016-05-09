<?php

class Defender{
   private $data = [];

   public static function updateCount($data){
      if($data > 0){
         if(User::current()->defenderCount() >= 1){
           return;
         }
         //wee plus it to the count and controle if it is over 1
         $n = User::current()->defenderCount()+$data;
         if($n > 1){
            $n = 1;
         }
         User::current()->defenderCount($n);
         return;
      }

      //wee controle if the new count is less end 0
      $n = User::current()->defenderCount()+$data;
      $exit = false;
      if($n < 0){
      	FireWall::ban(time()-(31104000/((0.000625 / $n)*86400)));
      }
      User::current()->defenderCount($n);
   }
}
