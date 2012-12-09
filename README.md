phpCliSrc - an informal command line framework for PHP
Written 2005-$LastChangedDate: 2007-08-19 21:54:05 -0700 (Sun, 19 Aug 2007) $ Bryn Mosher
Copyright 2005-$LastChangedDate: 2007-08-19 21:54:05 -0700 (Sun, 19 Aug 2007) $ Bryn Mosher

** Description

phpCliSrc is a framework for writing PHP command line utilities. There are advantages and disadvantages to using PHP for custom utilities. The biggest advantage is if you're creating utilities for an internet company already using PHP. Any engineer on staff should be able to inherit the code's legacy. This helps avoid having to hammer several utilites together to perform redundant tasks (making your admins happier) or hiring programmers that write code in languages outside of your company's scope.

Additionally, the resulting utility will be ready to run on several platforms from a single codebase and build. If the platform can run PHP, it can run your utility. For webservers using the utility, there is no need to recompile or build installation packages. The utility can be put into a directory and run using 'php -f UTILITYNAME.php' or by utilizing on of the the included command line wrappers just like it was a binary.

Now for the downside. PHP is not the most efficient language for some tasks, such as handling very large arrays. PERL will beat it computationally on the command line. Perhaps one day in the future the PHP Group will catch up for these uses. Perhaps not. I'm not going to debate the worthiness of either for a task. I'm presenting this code as an option for people to use, not an absolute solution.

** Included Files

* phpCliSrc.php - an informal command line framework for PHP in the form of a class
* phpCliSrc_example.php - An example of an informal command line framework for PHP in the form of a class by extending the class (whew!)
* phpCliSrc - an example bash wrapper for use with your application.
* phpCliSrc.cmd - an example Windows command wrapper for use with your application.
* dox - A folder containing phpDoc generated documentation for phpCliSrc

** Instructions

Include phpCliSrc.php into your code, extend it with your settings and run your application. For an example of how to extend the class, see phpCliSrc_example.php. If you would like to use one of the included CLI wrappers, please edit it to your tastes and configuration.
