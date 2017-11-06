# QK:Run
**Version history**   
1.0.0 First usable version

---
##What is it?
QK:Run is a little taskrunner that automates the processing of your project's javascript and css-files.
I've built it for my own use on small solo php-projects but, who knows, it might come in handy for someone else. In the current version there's no built-in *watch* feature to watch a given set of directories and/or files which limits the practical use for now. 

In version 1.0 QK:Run can
- minify javascript files
- crush CSS files (with CSS~Crush)
- concat javascript and css files

Maybe not much yet but QK:Run is made to be easilly extended to do more things.

*Crushing* by the way means preprocessing *and* (optional) minification of css files. See http://the-echoplex.net/csscrush/ 
 
##How to run 
QK:Run is implemented as command-line PHP. It needs PHP5.4 or higher. It expects Linux-style commands so I for example use it in a GitBash window on a windows machine. 
The QK:Run main file is `qk.php` in the `proj` folder. I've made an alias `qk` to start this so I don't have to type `php qk.php` all the time. From now on in my examples i'll use this short variant `qk` as well.

##Setup
The idea is to have one installation of QK:Run on your computer. This installation can serve multiple projects (called *sites* in QK:Run), one at a time. You can *point* QK:Run to the site currently used with the `select` command.

In the `proj` folder you'll find a `config` folder. In here you'll find the a `qkrun.config.php`. This is the *global* config file which should return a php associative array of key-value pairs. *key* is the name of the setting and *value* the value of the setting.  

The global config file should at least contain a `sites` setting. The `sites` setting is an array key-value pairs where the key is an acronym for a site (remember: site is the term for a QK:Run project) and the value is the name of the config file for this site.

       return ['sites' => ['dogs'=>'config/dogs.config.php',
                           'birds'=>'config/birds.config.php',],
              ];
    
In the example above you'll see that this QK:Run installation can handle two sites, *dogs* and *birds*. The configuration for *dogs* is in `config/dogs.config.php` and the configuration for *birds*  is in `config/birds.config.php`. This is the mechanism used to let QK:Run point to a selected site.    

The site config file is called the *local* config file. Like the global config file it needs to return a php-array of key-value pairs. These files can be part of the file structure of the site itself but like here in the example I've put them in the same folder as the global config file to have all configurations at one place. It doesn't matter. Note how the path in the example is a relative path seen from the `proj` folder though.

An important concept about QK:Run config files is that they are merged. Settings of the global and local file are combined. If the same setting occurs in both files the local setting overrides the global setting.  The whole idea is that you define settings which are the same for all your sites in the global config file and site specific settings in the local config file. With the current set of settings you probably won't be using this feature a lot but I expect it to become more relavant in future versions.


##Getting started
So to get started the first thing you'll want to do is to fill the `sites` setting in the `qkrun.config.php` file. Then you'll want to create and populate the local config file(s) you defined in the `sites` setting.

If we use the previous example of 

`'sites' => ['dogs'=>'config/dogs.config.php', 'birds'=>'config/birds.config.php', ]`   

in the global file then we'll need to create a `dogs.config.php` and a `birds.config.php` in the `config` folder. Let's focus on `dogs.config.php`..    

Typically it will look something like this:    

    return [
       'cssdir_in'   => '/mysites/dogs/css/',
       'cssdir_out'  => '/mysites/dogs/cssmin/',
       'jsdir_in'    => '/mysites/dogs/js/',
       'jsdir_out'   => '/mysites/dogs/jsmin/',
       'jsconc_in'   => '/mysites/dogs/jsmin/',
       'jsconc_out'  => '/mysites/dogs/jscon/',
       'jsconc_name' => 'dogs-min.js',
       'cssconc_in'  => '/mysites/dogs/cssmin/',
       'cssconc_out' => '/mysites/dogs/csscon/',
       'cssconc_name'=> 'dogs-min.css',
    ];

- `cssdir_in` defines where the input css files can be found
- `cssdir_out` defines where the 'crushed' css files are stored
- `jsdir_in` defines where the javascript files can be found
- `jsdir_out` defines where the minified javascript files are stored
- `jsconc_in` defines where the javascript files to concat are stored, typically the same as jsdir_out
- `jsconc_out` defines where the concatted javascript file is stored
- `jsconc_name` is the name of the concatted javascript file
- `cssconc_in` defines where the css files to concat are stored, typically the same as cssdir_out
- `cssconc_out` defines where the concatted css file is stored
- `cssconc_name` is the name of the concatted css file

## QK:Run commands
**select**    
select the site working on. Must be one of the acronyms in `sites`.

`--site=<siteacronym>` specifies the site you want to select.

**help**
show available commands

**crush**
*crushes* the css-files in `cssdir_in` to `cssdir_out`. If `xxx.css` is the input file the output file names becomes `xxx-min.css` if minified or stays `xxx.css` if no minification.  

`--nominify` specify this if you don't want minification. Minification is the default.     

**minify**
minify your javascript files in `jsdir_in` and write them to `jsdir_out`.

**jscon**
concat your javascript files in `jsconc_in` to one file in `jsconc_out` with the name `jsconc_name`. 

`--sort` specify this if you want the files processed sorted by alphabet. Default no sorting.

**csscon**
concat your css files in `cssconc_in` to one file in `cssconc_out` with the name `cssconc_name`.

`--sort` specify this if you want the files processed sorted by alphabet. Default no sorting.

**run**     
this is a combination of crush, minify, concss and conjs. See the individual commands.

##Future enhancements    
- A watch functionality, really important to make QK:Run interesting
- Implementation of commands by interfaces to allow for to switch to your own prefered module for implementation of a given command.
- Unit test functionality
