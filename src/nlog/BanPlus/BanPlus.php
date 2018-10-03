<?php

namespace nlog\BanPlus;

use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
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
    public $identify_key;


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
        $this->identify_key = $data['identify_key'] ?? [];

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->notice("BanPlus has been enabled.");
    }

    public function onDisable()/* : void /* TODO: uncomment this for next major version */ {
        file_put_contents($this->getDataFolder() . "database.json", json_encode(array_merge([
                'name' => $this->name,
                'ip' => $this->ip,
                'uuid' => $this->uuid,
                'xuid' => $this->xuid,
                'identify_key' => $this->identify_key
        ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function onDataPacketRecieve(DataPacketReceiveEvent $ev) {
        if ($ev->getPacket() instanceof LoginPacket) {
            $this->packets[TextFormat::clean($ev->getPacket()->username)] = clone $ev->getPacket();
        }
    }

    public function onPreLogin(PlayerLoginEvent $ev) {
        $pk = $this->packets[$ev->getPlayer()->getName()] ?? null;
        if ($this->isSubAccount($ev->getPlayer(), $pk, $name)) {
            $this->setAccountInformation($ev->getPlayer(), $pk, $this->getSubAccountName($ev->getPlayer(), $pk));
            $ev->setCancelled(true);
            $ev->setKickMessage('§c서버에서 부계정을 사용할 수 없습니다.\n§b본계정 §a: ' . $name);
        } else {
            $this->setAccountInformation($ev->getPlayer(), $pk, $this->getSubAccountName($ev->getPlayer(), $pk));
        }
    }

    protected function setAccountInformation(Player $player, ?LoginPacket $pk = null, ?string $orginalAccount = null): bool {
        $this->name[strtolower($player->getName())] = $orginalAccount ?? $player->getName();
        $this->ip[$player->getAddress()] = $orginalAccount ?? $player->getName();
        $this->uuid[$player->getUniqueId()->toString()] = $orginalAccount ?? $player->getName();
        $this->xuid[$player->getXuid()] = $orginalAccount ?? $player->getName();
        if ($pk instanceof LoginPacket) {
            $this->identify_key[$pk->identityPublicKey] = $orginalAccount ?? $player->getName();
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


        if ($pk instanceof LoginPacket && strcasecmp($player->getName(), TextFormat::clean($pk->username)) == 0 && isset($this->identify_key[strtolower($player->getName())])) {
            if (strcasecmp($this->identify_key[$pk->identityPublicKey], $player->getName()) != 0) {
                $result[] = $this->identify_key[$pk->identityPublicKey];
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
        return is_null($accountName = $this->getSubAccountName($player, $pk)) ? false : true;
    }

    /*
        public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
            if (!isset($args[0])) {
                $sender->sendMessage(self::$prefix . "/banall add <이름>");
                $sender->sendMessage(self::$prefix . "/banall remove <이름>");
                $sender->sendMessage(self::$prefix . "/banall list");
                $sender->sendMessage(self::$prefix . "/banall removeall");
                return true;
            }
    
            if ($args[0] === "add") {
                if (!isset($args[1])) {
                    $sender->sendMessage(self::$prefix . "/banall add <이름>");
                    return true;
                }
    
            }
        }
    */
}//클래스 괄호

?>
