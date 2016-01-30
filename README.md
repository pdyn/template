PDYN Template

A fast, simple Html template library with support for conditionals, loops, and includes.

==Usage:==

===In your PHP:===
1.Initialize the template class.
$template = new \pdyn\template\HtmlTemplate([[template directory]]);

2. Assign template files to the class, and give each an alias.
$template->file(['[[template file alias]]' => '[[template filename]]');

3. Assign global variables.
$template->assign_var($key, $value);

4. Assign sections.
$template->assign_sect($name, [$key => $value, $key2 => $value2]);

5. Display the template.
echo $template->display('[[template file alias]]');

===In your template files:===
Template files are simple HTML files with special tokens.

====Global variables====
Global variables are accessed like {key}. These will be replaced with the value you set using ->assign_var().

====Sections====
Sections serve as both conditionals and loops, depending on how many times you assigned them using ->assign_sect().

To start a section, use <!-- BEGIN sectionname --> on its own line, where sectionname is $name from step 4 above.
To end a section, use <!-- END sectionname --> on its own line, where sectionname is $name from step 4 above.

=====Conditionals=====
If you have not assigned a section for the name you use in these comments, the HTML inside them will not be shown, this serves
as a conditionl section of the template.

=====Looping=====
If you use ->assign_sect() multiple times, the HTML inside these comments will be shown that many times.

Inside the section start and end comments, you can use section variables. These are used in a template similarly to global variables
but start with the section name and a dot (.) character. For example {sectionname.key}.

If ->assign_sect() is called multiple times to loop the section, and different variables are used each time, then each time the HTML
 is looped, the corresponding set of variables is used.

For example, with the following ->assign_sect() calls
$template->assign_sect('count', ['number' => 'One']);
$template->assign_sect('count', ['number' => 'Two']);
$template->assign_sect('count', ['number' => 'Three']);

and the following HTML template:

Zero
<!-- BEGIN count -->
{count.number}
<!-- END count -->
Four

The following output would be rendered:
Zero
One
Two
Three
Four

=====Subsections=====
You can create sections within sections by using a dot character (.) inside the section name when calling ->assign_sect().

For example:
$template->assign_sect('one', ['name' => 'Test1']);
$template->assign_sect('one.two', ['name' => 'Test2']);

would create a section "two", inside a section "one". You use subsections in your templates by including an opening section comment
inside another section. For example
<!-- BEGIN one -->
{one.name}
<!-- BEGIN two -->
{one.two.name}
<!-- END two -->
<!-- END one -->

Subsections can be conditional or looped independently from their parents, for example, showing section "one", does not necessarily
show section two. Section two can be looped multiple times inside one loop of section one. Looping and conditionals follows what
you've called in your PHP. For example, the following PHP code:

$template->assign_sect('one');
$template->assign_sect('one');
$template->assign_sect('one.two');
$template->assign_sect('one');
$template->assign_sect('one.two');
$template->assign_sect('one.two');
$template->assign_sect('one.two.three');

Would show: section one without section two, section one with section two, section one with section two twice and section three in
the second loop of section two. Each loop of the parent and child sections could have its own set of variables associated with it.

Parent section variables can be accessed by child sections, but child variables cannot be accessed from parent sections.

