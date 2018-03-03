# Krona Loyality Points Module
This is my free loyality Points module for thirty bees. It offers two hooks, which can be used by module developers:

 - actionRegisterKronaAction
 - actionExecuteKronaAction

## Why should you hook to my module?
Genzo_krona is a powerful open source module, when it comes to loyality points in thirty bees. Maybe your customers have asked you, to implement any kind of reward function into your module. Krona tries to bring them all together. Just register your actions and execute them.

Example: You have a "question & answer" module. Instead of coding a complex reward system with points or vouchers, you hook into Krona. You could register the actions: "asked a question", "answered a question" and "best answer". Afterwards the merchant can define in Krona, how many points the customer gets for these actions.

## How to use the hooks?
**First** you need to register a hook during the installation of your module. I am sure, you are familiar with the following line:

    $this->registerHook('actionRegisterKronaAction')
Then you will return a simple array with the actions, you want to offer:

    public function hookActionRegisterKronaAction($params) {  
  
	    $actions = array(  
	        'ask_question',  
	        'give_answer',  
	        'best_answer'  
		  );
	 
	    return $actions;  
	}

**Second**  you will trigger these actions, when they happen. Example: After the saving process of a question, you will execute the second hook. Assuming your module has the name "super_questions", the code will look like:

    $hook = array(  
	    'module_name' => 'super_question',  
	    'action_name' => 'ask_question',  
	    'id_customer' => $this->context->customer->id,  
	    'action_message' => $message, // optional
	    'action_url' => 'https://www.domain.com/123-product#super_questions' // optional  
	);  
  
	Hook::exec('ActionExecuteKronaAction', $hook);

Thats all! Now Krona knows, that the customer has posted a question and will reward him with points. Let me explain the array a bit further. 

 - You have to use the official module_name.
 - The action_name is of course as you registered them in step 1. 
 - Just pass the id_customer. My method above is quite a save way.
 - action_message is optional. You need to send an array with all languages keys. To be honest: For most modules this isn't important.
 - action_url is optional. If you have a clear link to the action, please add the full url. In the example you would link directly to the posted question.

## Support
The following thirty bees module are already using Krona:
birthdaygift by SLiCK_303: https://github.com/SLiCK-303/birthdaygift
