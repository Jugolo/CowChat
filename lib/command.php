<?php
function error(PostData $data, string $code){
   bot_self($data->getChannel(), "/error ".$code);
}
