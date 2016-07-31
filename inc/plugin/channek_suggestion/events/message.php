namespace inc\plugin\channel_suggestion\events\message;

class MessageHandler implements Event{
    public function getEvents() : array{
        return [
             "ajax.message.resive" => [$this, "messageGet"]
        ];
    }

    public function messageGet(MessageParser $message){

    }
}
