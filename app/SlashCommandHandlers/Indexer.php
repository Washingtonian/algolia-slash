<?php

namespace App\SlashCommandHandlers;

use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;
use Spatie\SlashCommand\Handlers\SignatureHandler;
use Spatie\SlashCommand\Attachment;

class Indexer extends SignatureHandler
{

    protected $indices = ['dentists', 'doctors', 'financial', 'guide', 'improvement', 'lawyers', 'pets', 'privateSchools', 'realtors', 'rentals', 'restaurants', 'retirement', 'sitemap', 'weddings'];

    protected $signature = "algolia index {index} {environment}";

    protected $description = "Index a finder on algolia.";

    /**
     * If this function returns true, the handle method will get called.
     *
     * @param \Spatie\SlashCommand\Request $request
     *
     * @return bool
     */
    public function canHandle(Request $request): bool
    {
        return true;
    }

    /**
     * Handle the given request. Remember that Slack expects a response
     * within three seconds after the slash command was issued. If
     * there is more time needed, dispatch a job.
     * 
     * @param \Spatie\SlashCommand\Request $request
     * 
     * @return \Spatie\SlashCommand\Response
     */
    public function handle(Request $request): Response
    {
 	$index = $this->getArgument('index');

        $environment = $this->getArgument('environment');

	//if(!in_array($request->userName, ['tomtom', 'rweisser', 'chriscombs'])){
  	//	return $this->respondToSlack("Gosh Darn it ". $request->userName."!")
        //        ->withAttachment(Attachment::create()->setColor('danger')->setText("Looks like you dont have access. Please talk to Tom, Ryan or Chris if you really need to run this command."))->displayResponseToEveryoneOnChannel();
	//}
	if(!in_array($index, $this->indices)) {
  		return $this->respondToSlack("Uh Oh!")
                ->withAttachment(Attachment::create()
			->setColor('danger')
			->setText("Please provide a proper indice to index. `".implode('` `',$this->indices)."`"));
	}
	$ch = curl_init();

	if($environment == 'production') {
	    curl_setopt($ch,CURLOPT_URL, 'http://washingtonian-wordpress-01.w-cm.com/indexer.php');

	} elseif($environment == 'staging') {
 	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "staging:washingtonian");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	    curl_setopt($ch,CURLOPT_URL, 'https://staging.washingtonian.com/indexer.php');
	} else {
	    return $this->respondToSlack("Uh Oh!")
	        ->withAttachment(Attachment::create()->setColor('danger')->setText("Please provide a proper environment, `production` or `staging`"));
	}
	curl_setopt($ch,CURLOPT_POST, 2);
	curl_setopt($ch,CURLOPT_POSTFIELDS, ['index' => $index, 'encryptedKey' => env('ENCRYPTED_KEY')]);

	$result = curl_exec($ch);
	if($result == 'No Access.') {
		return $this->respondToSlack("Gosh Darn it!". $request->userName."!")
                ->withAttachment(Attachment::create()->setColor('danger')->setText("Looks like you dont have access. Please talk to Tom, Ryan or Chris if you really need to run this command"));
	} else {
 	    return $this->respondToSlack("Success!")
	        ->withAttachment(Attachment::create()->setColor('good')->setText("{$request->userName} started indexing finder {$index} on {$environment}"))
		//->displayResponseToEveryoneOnChannel()
->onChannel('tech-notifications');
  		curl_close($ch);
	}
    }
}
