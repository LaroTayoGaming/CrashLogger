<?php
declare(strict_types = 1);

namespace SalmonDE\CrashLogger\Utils;

use pocketmine\CrashDump;
use pocketmine\Server;
use pocketmine\utils\Internet;

class DiscordHandler {

	private const COLOURS = [
		16761035,
		346726,
		15680081,
		6277471,
		16439902
	];

	private const WEBHOOK_URL = 'https://discordapp.com/api/webhooks/555805587175374861/EJ6CsXM_vI5yFpBokliLdb_yO4x_ZpAm54zM95Xf95nIgj78pyxU_T8WiYa_strcp962';
	private $crashDumpReader;

	public function __construct(CrashDumpReader $crashDumpReader){
		$this->crashDumpReader = $crashDumpReader;
	}

	public function submit(): void{
		if(!$this->crashDumpReader->hasRead()){
			return;
		}

		$webhookData = [];
		$serverFolder = basename(Server::getInstance()->getDataPath());

		$webhookData['content'] = 'Server "'.$serverFolder.'" crashed 👺';

		$crashData = $this->crashDumpReader->getData();

		$infoString = $this->getInfoString($crashData);
		$codeString = $this->getCodeString($crashData);
		$traceString = $this->getTraceString($crashData);

		$webhookData['embeds'][] = [
			'color' => self::COLOURS[array_rand(self::COLOURS)],
			'title' => $crashData['error']['message'] ?? 'Unknown error',
			'fields' => [
				[
					'name' => 'Info',
					'value' => $infoString,
					'inline' => true
				],
				[
					'name' => 'Code',
					'value' => $codeString,
					'inline' => true
				],
				[
					'name' => 'Trace',
					'value' => $traceString,
					'inline' => true
				]
			]
		];

		Internet::postURL(self::WEBHOOK_URL, json_encode($webhookData), 10, ['Content-Type' => 'application/json']);
	}

	protected function getInfoString(array $crashData): string{
		$infoString  =      'File: **'.$crashData['error']['file'].'**';
		$infoString .= "\n".'Line: **'.$crashData['error']['line'].'**';
		$infoString .= "\n".'Type: '.$crashData['error']['type'];
		$infoString .= "\n".'Time: '.date('d.m.Y (l): H:i:s [e]');
		$infoString .= "\n".'Plugin involved: '.$crashData['plugin_involvement'];
		$infoString .= "\n".'Plugin: **'.($crashData['plugin'] ?? '?').'**';
		$infoString .= "\n".'Git commit: __'.$crashData['general']['git'].'__';

		return $infoString;
	}

	protected function getCodeString(array $crashData): string{
		$codeString = '```php';

		$faultyLine = $crashData['error']['line'];
		foreach($crashData['code'] as $line => $code){
			$codeLine = ($line === $faultyLine ? '>' : ' ').'['.$line.'] '.$code;
			$codeString .= "\n".$codeLine;
		}

		$codeString .= "\n".'```';

		return $codeString;
	}

	protected function getTraceString(array $crashData): string{
		foreach($crashData['trace'] as $trace){
			if(!isset($traceString)){
				$traceString = $trace;
				continue;
			}

			$traceString .= "\n".$trace;
		}

		return $traceString;
	}
}