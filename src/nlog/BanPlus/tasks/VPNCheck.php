<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 2019-03-17
 * Time: 오전 8:33
 */

namespace nlog\BanPlus\tasks;

use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;

class VPNCheck extends AsyncTask {

    /** @var string */
    private $name, $ip;

    /** @var bool */
    private $useVPN;

    public function __construct(string $name, string $ip) {
        $this->name = $name;
        $this->ip = $ip;
        $this->useVPN = true;
    }

    public function onRun(): void {
        $url = "http://ip-api.com/json/" . $this->ip;
        $json = json_decode(Internet::getURL($url, 5, [], $err, $headers, $httpCode), true);
        if ($httpCode !== 200) {
            $this->useVPN = false;
        }
        if (is_array($json) && ($json['timezone'] ?? 'Asia/Seoul') === 'Asia/Seoul') {
            $this->useVPN = false;
        }
    }

    public function onCompletion(): void {
        $server = Server::getInstance();
        $player = $server->getPlayerExact($this->name);
        if ($this->useVPN) {
            $server->getNameBans()->addBan($this->name, "VPN 사용으로 계정이 차단되었습니다.\n해외 거주 시 밴드 게시글로 문의해주시기 바랍니다.");
            if ($player instanceof Player) {
                $player->kick("VPN 사용으로 계정이 차단되었습니다.", false, " ");
            }
        }
    }

}