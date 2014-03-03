vnn-high-fiver
==============
 I built it using the LAMP stack and Symfony 2.4.2 Framework.  I installed Symfony 2.4.2 using Composer.

And, I have been wanting to try out the '[Goutte](https://github.com/fabpot/Goutte)' screen scraping and web crawling library for PHP
written by the author of Symfony2.  I installed it via Composer as well and have enjoyed playing around with it.

For charts, I used [Google Charts JavaScript API](https://developers.google.com/chart/) to build quick, and quite pretty charts.

You can access the system online at:  [http://fiver.ronniemoore.com](http://fiver.ronniemoore.com)

I didn't worry about the UI (per your directions), and focused on the functionality and making the system
actually work. :)  It pulls the data directly from Varvee.com on each page view, and does not use or save
anything to a database.

On the home page, with the Top 5 Players list, I sorted them by their average points per game (PPG).
In the specific URL for the Indiana Basketball (Boys) results, there were actually multiple players with
the same PPG.  I felt it was appropriate to list ties until the total of five ranks were displayed
(currently looks like a total of 9 players make up the top 5 spots)

Clicking a player's name will pull up the player details page.  It has their school, and location, and the
summary table of their games.  Below that you'll see it graphed out as well as a line graph, with listings
for the Team Score and Player Score.  You can also hover over the points on the graph and it will display
the information as well.

I have created a "Testing Methodology" page (link on bottom of the main page.  I have described how I use
phpUnit for unit and functional testing, and how I have also used Selenium RC for browser-based testing
of functionality.

The DomCrawler filtering code was pretty fun to write, and its pretty robust, using the site's CSS path
for elements instead of XPath or (heaven forbid) Regex parsing of the output.

If I had more time I would complete deeper unit and functional tests and the bonus.

I think this programming challenge gave me a good opportunity to show more deeply what I'm able to do.

Thanks,

Ronnie Moore