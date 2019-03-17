<?php

namespace nlog\BanPlus;

use nlog\BanPlus\tasks\VPNCheck;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class BanPlus extends PluginBase implements Listener {

    /** @var string */
    public static $prefix = "§c§l[밴시스템] §r§7";

    /** @var BanPlus|null */
    private static $instance = null;

    private static function getInstance(): ?BanPlus {
        return self::$instance;
    }


    /**
     * TODO: 나중에 쓸거
     * @var array
     */
    private static $banMessage = [
            "Disconnected from server",
            "timeout",
            "Login timeout",
            "서버와의 연결이 끊겼습니다."
    ];


    /** @var array */
    public $name;

    /** @var array */
    public $ip;

    /** @var array */
    public $uuid;

    /** @var array */
    public $xuid;

    /** @var array */
    public $deviceId;

    /** @var LoginPacket[] */
    public $packets;

    public function onEnable() {
        $data = [];
        if (file_exists($path = $this->getDataFolder() . "db.json")) {
            $data = json_decode(file_get_contents($path), true) ?? [];
        }
        $this->name = $data['name'] ?? [];
        $this->ip = $data['ip'] ?? [];
        $this->uuid = $data['uuid'] ?? [];
        $this->xuid = $data['xuid'] ?? [];
        $this->deviceId = $data['deviceId'] ?? [];

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->notice("BanPlus has been enabled.");
    }

    public function onDisable()/* : void /* TODO: uncomment this for next major version */ {
        file_put_contents($this->getDataFolder() . "database.json", json_encode(array_merge([
                'name' => $this->name,
                'ip' => $this->ip,
                'uuid' => $this->uuid,
                'xuid' => $this->xuid,
                'deviceId' => $this->deviceId
        ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function onDataPacketRecieve(DataPacketReceiveEvent $ev) {
        /** @var $pk LoginPacket */
        if (($pk = $ev->getPacket()) instanceof LoginPacket) {
            $this->packets[TextFormat::clean($pk->playerInfo->getUsername())] = clone $pk;
        }
    }

    public function onLogin(PlayerLoginEvent $ev) {
        $pk = $this->packets[$ev->getPlayer()->getName()] ?? null;
        if ($this->isSubAccount($ev->getPlayer(), $pk, $name)) {
            $this->setAccountInformation($ev->getPlayer(), $pk, $this->getSubAccountName($ev->getPlayer(), $pk));
            $ev->setCancelled(true);
            $ev->setKickMessage('§c서버에서 부계정을 사용할 수 없습니다.\n§b본계정 §a: ' . $name);
        } else {
            $this->setAccountInformation($ev->getPlayer(), $pk, $this->getSubAccountName($ev->getPlayer(), $pk));

            $this->getServer()->getAsyncPool()->submitTask(new VPNCheck($ev->getPlayer()->getName(), $ev->getPlayer()->getAddress()));
        }
    }

    protected function setAccountInformation(Player $player, ?LoginPacket $pk = null, ?string $orginalAccount = null): bool {
        $this->name[strtolower($player->getName())] = $orginalAccount ?? $player->getName();
        $this->ip[$player->getAddress()] = $orginalAccount ?? $player->getName();
        $this->uuid[$player->getUniqueId()->toString()] = $orginalAccount ?? $player->getName();
        $this->xuid[$player->getXuid()] = $orginalAccount ?? $player->getName();
        if ($pk instanceof LoginPacket) {
            $this->deviceId[$pk->clientData['DeviceId']] = $orginalAccount ?? $player->getName();
        }
        return true;
    }

    /**
     * @param Player $player
     * @param null|LoginPacket $pk
     * @return null|string
     */
    public function getSubAccountName(Player $player, ?LoginPacket $pk = null): ?string {
        $result = [];
        if (isset($this->name[strtolower($player->getName())])) {
            if (strcasecmp($this->name[strtolower($player->getName())], $player->getName()) != 0) {
                $result[] = $this->name[strtolower($player->getName())];
            }
        }


        if (isset($this->ip[strtolower($player->getAddress())])) {
            if (strcasecmp($this->ip[$player->getAddress()], $player->getName()) != 0) {
                $result[] = $this->ip[$player->getAddress()];
            }
        }


        if (isset($this->uuid[strtolower($player->getUniqueId()->toString())])) {
            if (strcasecmp($this->uuid[$player->getUniqueId()->toString()], $player->getName()) != 0) {
                $result[] = $this->uuid[$player->getUniqueId()->toString()];
            }
        }


        if (isset($this->xuid[strtolower($player->getXuid())])) {
            if (strcasecmp($this->xuid[$player->getXuid()], $player->getName()) != 0) {
                $result[] = $this->xuid[$player->getXuid()];
            }
        }


        if ($pk instanceof LoginPacket && strcasecmp($player->getName(), TextFormat::clean($pk->playerInfo->getUsername())) == 0 && isset($this->deviceId[strtolower($player->getName())])) {
            if (strcasecmp($this->deviceId[$pk->clientData['DeviceId']], $player->getName()) != 0) {
                $result[] = $this->deviceId[$pk->clientData['DeviceId']];
            }
        }

        return $result[0] ?? null;
    }

    /**
     * @param Player $player
     * @param null|LoginPacket $pk
     * @return bool
     */
    public function isSubAccount(Player $player, ?LoginPacket $pk = null, ?string &$accountName = null): bool {
        return ($accountName = $this->getSubAccountName($player, $pk)) !== null;
    }
}//클래스 괄호

?>
