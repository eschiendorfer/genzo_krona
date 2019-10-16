<div class="clearfix">
    {include file="./sidebar.tpl"}

    <div class="col-md-10">
        <div class="panel col-lg-12">
            <div class="panel-heading">{l s='Bug Reporting' mod='genzo_krona'}</div>
            <p><b>Github:</b> <a target="_blank" href="https://github.com/eschiendorfer/genzo_krona">https://github.com/eschiendorfer/genzo_krona</a></p>
            <p><b>Forum:</b> <a target="_blank" href="https://forum.thirtybees.com/topic/1505-1505/free-module-loyalty-points-genzo_krona/">https://forum.thirtybees.com/topic/1505-1505/free-module-loyalty-points-genzo_krona/</a></p>
        </div>
        <div class="panel col-lg-12">
            <div class="panel-heading">{l s='Do you like this module?' mod='genzo_krona'}</div>
            <div style="margin-right: 3%;" class="support-box">
                <p><b>{l s='Option 1: I like pizza and beer ;)' mod='genzo_krona'}</b></p>
                <p>{l s='If you want to make a donation to me for this module. Here is my paypal Account:' mod='genzo_krona'}</p>
                <a href="https://www.paypal.me/ESchiendorfer" target="_blank">paypal.me/ESchiendorfer</a>
            </div>
            <div style="margin-right: 3%;" class="support-box">
                <p><b>{l s='Option 2: My store likes links' mod='genzo_krona'}</b></p>
                <p>{l s='I am a merchant myself. If you could link my store spielezar.ch, it would help me a lot!' mod='genzo_krona'}</b></p>
                <a href="https://www.spielezar.ch" target="_blank">https://www.spielezar.ch</a>
            </div>
            <div class="support-box">
                <p><b>{l s='Option 3: thirty bees likes Supporters' mod='genzo_krona'}</b></p>
                <p>{l s='Thirty bees is a wonderful open source project. It will become even more powerful, if you support it as a Backer!' mod='genzo_krona'}</b></p>
                <a href="https://forum.thirtybees.com/support-thirty-bees/">{l s='Become a Supporter!' mod='genzo_krona'}</a>
            </div>
        </div>
        <div id="krona-docs" class="panel col-lg-12">
            <div class="panel-heading">{l s='Documentation' mod='genzo_krona'}</div>
            <p>This loyalty module has the official name “genzo_krona”, but I am just calling it Krona in this Documentation. With Krona you get a powerful loyalty / gamification module. It’s very flexible and can be expanded with other modules. The best thing is, Krona is open source and completely free.</p>
            <h2>Why to use this module?</h2>
            <p>The strength of this modules is, that other modules can hook in. Do you want to reward, when a customer is writing a review? No problem. Just install <a href="https://forum.thirtybees.com/topic/1422-free-modulerevws-product-reviews" target="_blank">revws module</a> by datakick and you can reward this action.
                Do you want to give points, when a customer has birthday? No problem. Just install slick-303 <a href="https://forum.thirtybees.com/topic/1540-1540/free-module-birthday-gift/" target="_blank">birthday module.</a></p>
            <p>If you are looking for a simple module, which just rewards when a customer places an order. Then you don’t need this module. Though it’s probably still a good choice. Krona is much more than a basic loyalty system as you will learn on the next sections.</p>
            <p>Please note: that Krona is quite a complex module. It was not only complex to code, but it’s also quite complex to set up, since it fits so many use cases. I recommend to do the following steps after installation:</p>
            <ol>
                <li>Go into all Settings tabs and save them first.</li>
                <li>Go to Orders and enable/edit your currency.</li>
                <li>Import your players, with the options you like.</li>
                <li>Go to Groups and organize those.</li>
                <li>Go to Actions and edit/enable the ones you want to use.</li>
                <li>Then setup some Levels and you’re good to go.</li>
            </ol>
            <p>Krona has almost unlimited use cases. Here are just a few examples how you could use it.</p>
            <ul>
                <li>Reward orders and let customers convert loyalty points into coupons.</li>
                <li>Reward actions (like posting a review) and give them a coupon for it.</li>
                <li>Reward a customer with a new customer group, when they placed they 3<sup>rd</sup> order.</li>
                <li>Publish a leaderboard with the players and motivate them to collect more points.</li>
            </ul>
            <p>Actions, Orders and Levels are the heart of Krona. In this documentation the headings are listed like the sidebar in the backoffice. I strongly recommend to read the whole documentation, after installing Krona for the first time.</p>

            <h2>Actions</h2>
            <a href="{$img_docs}actions-list.jpg" class="fancybox"><img src="{$img_docs}actions-list.jpg"></a>
            <p>After installing you can already use four inbuilt functions. These are: account creation, page visit, avatar upload and newsletter subscription. But you can expand these actions with other modules. It’s quite simple for a module developer to hook into Krona, it will take them only an hour or so. So don’t hesitate to ask them, using the Krona interface.</p>
            <a href="{$img_docs}action-account-creation.jpg" class="fancybox"><img src="{$img_docs}action-account-creation.jpg"></a>
            <p>This is the typical editing view of an action. You can set a “<strong>title</strong>” and a “<strong>message</strong>”, which will be displayed in the customer timeline in the frontoffice. </p>
            <p>With the “<strong>execution type</strong>” you can prevent, that a customer collects unlimited points, by always repeating the same action. Let’s say, you want to give points, when a customer has birthday. We all know these very clever guys, who will change their birthday every day, so they collect massive amount of points. Not with us! Just set up the execution type to “max per year” and “<strong>execution max</strong>” to 1. It means, that a customer can only collect points once a year for this action. You can do the same for month, day and lifetime. It’s also possible, to set it to unlimited. </p>
            <p>The “<strong>points change</strong>” value is the value a customer gets, when executing this action. In the screenshot a player get’s 100 points when he creates his account. This can be seen as a starting point, since creation the account is always the first action.</p>

            <h2>Orders</h2>
            <a href="{$img_docs}orders-list.jpg" class="fancybox"><img src="{$img_docs}orders-list.jpg"></a>
            <p>Orders could be called the classical way of a loyalty system. You reward customers, who buy products in your store. You can set this up depending on the currency. But keep in mind that some settings are saved globally. This is described under “Settings”.</p>
            <p>As you can see orders are rewarded with coins. No, that’s not a typo. This is the basic concept of Krona. <strong>Actions are rewarded with points and orders with coins.</strong></p>
            <a href="{$img_docs}order-dollar.jpg" class="fancybox"><img src="{$img_docs}order-dollar.jpg"></a>
            <p>The “<strong>coins reward</strong>” value defines, how many coins a customer gets, when he spends 1 Dollar (or any other currency). I recommend to set this value to 1, 10 or 100. </p>
            <p>You can prevent, that small orders are rewarded with coins. If you set “<strong>minimum amount</strong>” to 20 USD, only higher orders than 20 USD will be rewarded. To be honest: I don’t recommend to use this feature, as it confuses customers. It was just implemented, since it’s a part of the native loyalty module from thirty bees.</p>
            <p>You can prevent, that customers collectto many coins with one order. Just define a value in “<strong>max coins change</strong>”. I am not a fan of such methods, since it's not really customer friendly (who spends a lot, should get a lot of rewards), but it's up to you.</p>
            <p>The “<strong>loyalty conversion</strong>” value defines how worthy the loyalty points are. This may sound confusing to you, when you are reading this docs the first time. Under “Settings” I will describe the relation between coins and loyalty points in detail. If you set it to 0.01, 1 loyalty point is worth 1 cent.</p>
            <h3>Referral</h3>
            <p>Since version 2.0.0 it's possible to use referrals. Every customer has a <b>"referral code"</b> in his FO account overview. He gives this code, to his friend. The friends types the code during the registraion process.</p>
            <p>Thats the reason you can set up a <b>"coins reward referrer"</b> and a <b>"coins reward buyer"</b>. If the friend places an order he will get "coins reward buyer". In order to be a reward, this needs always to be higher than the normal coins reward.</p>
            <p>An example: your basic coins change is 1 coin/$. Your coins reward buyer equals 1,5 coins/$ and your coins reward referrer equals 0,5. This means you give the double amount of loyalty points in referral orders than in normal orders. An orer with for 50$ will bring 75 coins to the buyer and 25 coins to the referrer.</p>
            <p>In the settings you can limit on how many orders the coins change for referrer and coins change for buyer should be active. Normally the merchants limit this special reward to the first order.</p>

            <h2>Players</h2>
            <a href="{$img_docs}players-import.jpg" class="fancybox"><img src="{$img_docs}players-import.jpg"></a>
            <p>You can <strong>import</strong> all your customers, so they become part of Krona. If you click on the import button they will all be imported. If you were using the native loyalty module, you can import their loyalty points balance into Krona. I recommend, to set this value to 1. So your customers will have the same value as before. </p>
            <p>You can even reward your customers for <strong>old orders</strong> they placed. It’s important, that you have set up the “Orders” for all currencies and that they are active. Otherwise it won’t work. </p>
            <p>Note: I recommend, to import players either at the very beginning of using this module or never. </p>
            <a href="{$img_docs}players-list.jpg" class="fancybox"><img src="{$img_docs}players-list.jpg"></a>
            <p>In the player list you will see a lot of columns depend on your settings. The active value can be changed by the customer in the frontoffice too. We are basically offering them a way to say: “Nope, I am not interested in the loyalty program.” I guess, we should respect that. The banned option, can only be set up from the backoffice. If a customer is cheating, you can block him from participating any further.</p>
            <a href="{$img_docs}players-edit.jpg" class="fancybox"><img src="{$img_docs}players-edit.jpg"></a>
            <a href="{$img_docs}players-edit-2.jpg" class="fancybox"><img src="{$img_docs}players-edit-2.jpg"></a>
            <p>In the edit view you can’t change points or coins directly, since this would mess things up with “levels”. But you can add “<strong>custom actions</strong>”. There you can change points and coins in any direction.</p>
            <p>If for example somebody writes useless reviews (spam), you could make a custom action, which is like a penalty. You take off the points, he has collected before. It’s like a warning. If he continues, you still could ban him. But of course I hope, you aren’t forced, to take such measurements.</p>
            <p>Since version 2.0.0 it's also possible to edit or delete entries from the player history.</p>

            <h2>Levels</h2>
            <a href="{$img_docs}levels-list.jpg" class="fancybox"><img src="{$img_docs}levels-list.jpg"></a>
            <p>Levels are very powerful tool to reward your customers. In the “<strong>condition type</strong>” you set up, what a customers has to fulfill for reaching this level. If you use a threshold type, a customers needs to collect that many points or coins to reach the level. If you use an executing type, you can reward things like: giving a coupon after somebody placed his third order. If you are confused about the word “lifetime points”, read the sections “Settings”. </p>
            <p>Under “<strong>condition time span</strong>” you define, how much time the customer has, to fulfill a condition. It’s counted in days. This can be useful, if you want your customer to execute actions or orders regularly. Example: It’s possible to reward a customer with a VIP group, if he places five orders a year. </p>
            <p>Let’s go on with the VIP example. Under “<strong>duration</strong>” you define how long this level will be hold. Often it’s wished to be unlimited, but not in the VIP example. You maybe want to take off this VIP group after a year again. So you set the duration to 365. Krona is clever enough to renew the level. It means, if the customer has placed again five orders, he will stay on VIP.</p>
            <p>You can use three “<strong>reward types</strong>”. Symbolic, coupon and group. With symbolic you can use Krona as a gamification module. So users are collecting badges / icons. This can be quite fun in an active community. But of course to reward with a coupon or VIP group is more attractive. </p>
            <p>In Krona you can even restrict how often a level can be achieved. This setting is interesting, when you reward with coupons or groups. You maybe want to give a 10% coupon, when a customer places his first review. But posting a second review shouldn’t be rewarded again. So you set the “<strong>achieve max</strong>” equal to 1.</p>
            <h2>Coupons</h2>
            <a href="{$img_docs}coupons-list.jpg" class="fancybox"><img src="{$img_docs}coupons-list.jpg"></a>
            <p>After the installation of Krona, you should see a coupon called “KronaTemplate: Orders”. You will find this coupon as well under “Price Rules -&gt; Cart Rules”. Why so? Krona is using the core coupon system of thirty bees. That’s how it works:</p>
            <ol>
                <li>Create a cart rule, as you want the coupon to be. The name has to begin with "KronaTemplate:". Example: "KronaTemplate: 10% Coupon".</li>
                <li>Deactivate the just created cart rule, since it's just a helper template. Your customer will get individual and active coupons with the same conditions.</li>
                <li>Go to coupons tab in Krona and check, if the just created coupon shows up.</li>
                <li>From now on, you can select this coupon as a reward in levels.</li>
            </ol>
            <p><strong>Warning:</strong> Never delete any core cart rule, which is used as a template. In other words: Never delete a cart rule beginning with "KronaTemplate:"!</p>
            <p>Note: The customer will see the coupon name without the "KronaTemplate:" part. In the example above he would see "10% Coupon".</p>
            <p>You may wonder, what is the “KronaTemplate: Orders” coupon about. This is relevant, if you use the loyalty function. Your customer will be able to convert loyalty points, into a coupon in the frontoffice:</p>
            <a href="{$img_docs}coupon-conversion.jpg" class="fancybox"><img src="{$img_docs}coupon-conversion.jpg"></a>
            <p>In this conversion process the coupon will be created as you set it up in “KronaTemplate: Orders”. You can for example set up a time span of one month and the coupon will be valid for month, when a customer converts loyalty points.</p>
            <p>Since version 2.0.0 you can also allow your customers, to convert loyalty points directly at the checkout. Note: The technical process is the same. First a coupon is generated. Second the coupon is applied. But this happens all automated.</p>

            <h2>Groups</h2>
            <a href="{$img_docs}groups-list.jpg" class="fancybox"><img src="{$img_docs}groups-list.jpg"></a>
            <p>Here you will see all your customer groups. In order to make Krona fully working, the module needs to know about the importance / priority of each group. Remember the VIP example? If this customer group is removed after one year, the customer will be connected to next highest customer group, which he has achieved.</p>

            <h2>Settings</h2>
            <p>On the top of the settings form you see your “<strong>cron job url</strong>”. Please set it up and let it execute daily. I guess it’s best to let it execute just some minutes after midnight. Why is a cronjob needed? It’s important, if you have levels with a duration time. It’s also needed for the inbuilt newsletter action. That way it’s possible, to reward a customer every month, as long as he is subscribed to your newsletter. If you want to expire loyalty points, you also need the cron job.</p>
            <h3>General</h3>
            <a href="{$img_docs}settings-general.jpg" class="fancybox"><img src="{$img_docs}settings-general.jpg"></a>
            <p>When you use Krona for the first time. You should ask yourself: what kind of system do I actually want? In general there are two use cases: <strong>loyalty and gamification</strong>. You can use either one of them or combine them. </p>
            <p>The loyalty function is working very similar to the native loyalty module. You reward, when a customer places an order. He can convert his loyalty points into coupons. The idea of gamification is, that your customers get motivated to collect badges or leading the ranking. Does it mean gamification has no rewards? No, not at all. You can still reward through levels. The difference is, that a customer can not convert the points, he has collected.</p>
            <p>In consequence <strong>loyalty points</strong> can go up and down. Gamification points can be seen as <strong>lifetime points</strong>, since they only go up. The only way they could go down, is when you give a penalty trough a custom action.</p>
            <p>Under “<strong>name</strong>” you specify a unique name for your loyalty program. The customer will see this name in the frontoffice. In my case it’s called “Krona”, which explains the name of the module ;)</p>
            <p>Under “<strong>url</strong>” you specify the url, where your loyalty program can be reached. If you save it with “loyalty”, you can reach it by yourdomain.com/loyalty.</p>
            <p>If you deactivate “<strong>customer activation</strong>”, your customer have to activate themselves, to be a part of your loyalty system. It means that they don’t collect any points until they activate it. I recommend strongly, to set it to “yes”. </p>
            <p>The “<strong>home content</strong>” is displayed, when a user is going to yourdomain.com/yoururl. It’s like a cms page, where you can describe, how your system works and what it is all about.</p>

            <h3>Orders</h3>
            <a href="{$img_docs}settings-orders.jpg" class="fancybox"><img src="{$img_docs}settings-orders.jpg"></a>
            <p>On orders you can set up a “<strong>total amount</strong>”. This amount will be used, to calculate the coins a customer collects, when he places an order. I recommend to use a value WITH tax. Otherwise your customers will be easily confused.</p>
            <p>In “<strong>rounding</strong>” I recommend to use up. It sounds better to the customer to round up.</p>
            <p>Be careful to select all valid “<strong>order states</strong>”. Always when an order status is updated, it will check, if this new status is relevant. If the order state is relevant the coins will be calculated newly. Same is true if the order state is not relevant. In this case the coins will be equal zero.</p>

            <h3>Loyalty</h3>
            <a href="{$img_docs}settings-loyalty.jpg" class="fancybox"><img src="{$img_docs}settings-loyalty.jpg"></a>
            <p>What is “<strong>loyalty total value</strong>”? Remember actions are rewarded with points, but orders are rewarded with coins. Your customers will never hear of points and coins. They just see the total loyalty value. You can decide, how this value is calculated.</p>
            <p>Remember the loyalty function is basically about converting total points into coupons by the customer itself. If you want to have the same set up like the native loyalty module, you would set total value to coins. I guess, that is the normal use case of loyalty. </p>
            <p>You can specify a “<strong>loyalty points name</strong>”. This will be displayed in frontoffice. The logical name is “loyalty points” but it’s up to you. When you don’t use gamification, you can go for the simple name “points”.</p>
            <a href="{$img_docs}product-page.jpg" class="fancybox"><img src="{$img_docs}product-page.jpg"></a>
            <p>If you switch “<strong>product page</strong>” on. The hook “DisplayRightColumnProduct” will show up information like the above screenshot. You can change the hook manually to “DisplayProductButtons”. Don’t forget to unhook the DisplayRightColumnProduct, if you want to change.</p>
            <a href="{$img_docs}shopping-cart.jpg" class="fancybox"><img src="{$img_docs}shopping-cart.jpg"></a>
            <p>You can also show a little message containing the loyalty points an order will bring. Just switch on “<strong>cart page</strong>”.</p>
            <p>Since version 2.0.0 it's possible to <b>expire loyalty points</b>. There are two methods: fixed and flexible. Fixed means that the expire date never updates.</p>
            <p>Example: your customer makes an order. Next day he places a second order. The coins from the first order will expire a day before the points from the second order. If you chose the flexible way, they will both expire on the expire date from the second order. So the expire date of the first order was prolonged.</p>
            <p>There is an update function for all expire dates. This is useful, if you import orders and want to define an expire date on these. But be careful: This update will override all existing expiring dates!</p>

            <h3>Gamification</h3>
            <a href="{$img_docs}settings-gamification.jpg" class="fancybox"><img src="{$img_docs}settings-gamification.jpg"></a>
            <p>The concept of “<strong>gamification total value</strong>” is equal to “loyalty total value”. The customer will only see the total value in frontoffice. However here the option coins + points is probably the most chosen option. If you set it to points only, then you have a clear distinction between actions and orders.</p>
            <p>You can specify a “<strong>gamification points name</strong>”. This will be displayed in frontoffice. The logical name is “lifetime points”, but it’s up to you. When you don’t use loyalty, you can go for the simple name “points”.</p>
            <a href="{$img_docs}settings-gamification-leaderboard.jpg" class="fancybox"><img src="{$img_docs}settings-gamification-leaderboard.jpg"></a>
            <p>Select a <strong>display name format</strong> which is used for displaying the leaderboard. Keep in mind, that normally people don’t like to be shown with their full names. I like the “John D.” format the most.</p>
            <p>You can also allow to use “<strong>pseudonym</strong>”. A customer can save it in his customer account. You can also change it through the backoffice in the player edit view.</p>
            <p>You can allow customers to upload an “<strong>avatar</strong>”. This is one of the inbuilt actions. It will make your leaderboard look more attractive. </p>
            <p>Note: Other modules can use pseudonym and avatar. So if a module developer integrates Krona nicely, pseudonym and avatar will show up, when posting a review for example.</p>

            <h3>Coupons</h3>
            <a href="{$img_docs}coupons-list.jpg" class="fancybox"><img src="{$img_docs}coupons-list.jpg"></a>
            <p>You can set up a “<strong>prefix</strong>” for the coupons, which are created by Krona. This way you will always notice in an order, if this coupon was created through Krona module. </p>
        </div>
    </div>
</div>