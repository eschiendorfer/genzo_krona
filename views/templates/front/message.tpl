{if $hist.type == "Coupon"}
	Congratulations! You have reached a new Level. In return you received a Coupon.

<!-- One Time Actions -->
{elseif $hist.type =="Account"}
	You have successfully created a customer account.
{elseif $hist.type =="Avatar"}
	You have added an avatar.
{elseif $hist.type =="Newsletter"}
	You have subscribed to our newsletter.

<!-- Questions & Answers -->    
{elseif $hist.type =="Question"}
	You have asked a question.
{elseif $hist.type =="Answer"}
	You have given an answer.
{elseif $hist.type =="Answered"}
	You have selected a best answer for your question.
{elseif $hist.type =="Best Answer"}
	Your answer has been selected as the best.
  
{/if}