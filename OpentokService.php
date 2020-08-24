<?php

declare(strict_types=1);

namespace Modules\Meeting\Services;

use OpenTok\OpenTok;
use OpenTok\MediaMode;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Models\Meeting;
use Modules\Meeting\Models\MeetingGroup;
use Exception;

class OpentokService
{
    protected $openTokAPI = null;
    public $api_key = '46884074';
    protected $api_secret = '1b66c8d8c5cc005c409536268c9a519c61746907';
    protected $session = null;
    protected $session_token = null;
    protected $console;

    public function __construct(Command $console = null)
    {

        $this->console = $console;

        if (empty($this->api_key) && empty($this->api_secret)) {

            $this->console->error('Missing keys to create Opentok Session');
        }

        $this->openTokAPI = new OpenTok($this->api_key, $this->api_secret);
    }

    public function create() 
    {
        
        $dataSession = [];
        
        $this->session = $this->openTokAPI->createSession(array('mediaMode' => MediaMode::ROUTED));
        
        $this->session_token = $this->openTokAPI->generateToken($this->session->getSessionId(), [
            'exerciseireTime' => time()+ 60,
            'data'       => "Some data to put into generated token"
        ]);

        $dataSession['api_key'] = $this->api_key;
        $dataSession['session_id'] = $this->session->getSessionId();
        $dataSession['session_token'] = $this->session_token;

        return $dataSession;
    }

    public function update(array $data, Meeting $meeting): Meeting
    {
        
        return DB::transaction(function () use ($data, $meeting) {

            $meeting->fill($data);
            $meeting->save();

            return $meeting;
        });
    }

    // public function join(array $data)
    // {

    // }

}
    