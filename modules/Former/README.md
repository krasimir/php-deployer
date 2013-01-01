# Dealing with forms

A common case is that we have to show a html form, submit it and collect its data. Normally there are some validations and also usage of the same form for editing already stored data. There should be some nice way to deal with all those repeatable tasks and follow [DRY](http://en.wikipedia.org/wiki/Don't_repeat_yourself) principle. Actually I didn't find any simple and elegant solution of the problem. So, this module is for that - simply dealing with forms.

## Create a form

    // pass a unique name and action url
    $form = Former::register("register-user", "/examples/former/");

    // you can pass also the request method (default = POST)
    $form = Former::register("register-user", "/examples/former/", "GET");

## Adding controls    

#### Text field

    $form->addTextBox(array(
        "name" => "username", 
        "label" => "Your name:"
    ));

#### Text area

    $form->addTextArea(array(
        "name" => "description", 
        "label" => "Few words about you:"
    ));

#### Password text box

    $form->addPasswordBox(array(
        "name" => "password", 
        "label" => "Your password:"
    ));

#### Drop-down menu

    $form->addDropDown(array(
        "name" => "city",
        "label" => "Your city:",
        "options" => array(
            "none" => "None",
            "new-york" => "New York",
            "london" => "London",
            "paris" => "Paris"
        )
    ));

#### Radio buttons

    $form->addRadio(array(
        "name" => "job",
        "label" => "Your job:",
        "options" => array(
            "none" => "None",
            "front-end" => "Front-end developer",
            "back-end" => "Back-end developer"
        )
    ));

#### Checkboxes

    $form->addCheck(array(
        "name" => "special-wishes",
        "label" => "Special wishes:",
        "options" => array(
            "w1" => "fresh water",
            "w2" => "fruits",
            "w3" => "dentist"
        )
    ));

#### File control

    $form->addFile(array(
        "name" => "avatar",
        "label" => "Please choose your avatar:"
    ));

Have in mind that you can chain the controls:

    $form->addTextBox(array(
        "name" => "username", 
        "label" => "Your name:", 
        "validation" => Former::validation()->NotEmpty()->LengthMoreThen(5)->String()
    ))
    ->addTextArea(array(
        "name" => "description", 
        "label" => "Few words about you:"
    ))
    ->addPasswordBox(array(
        "name" => "password", 
        "label" => "Your password:", 
        "validation" => Former::validation()->NotEmpty()->LengthMoreThen(5)
    ))
    ->addTextBox(array(
        "name" => "salary", 
        "label" => "Your prefered salary:", 
        "validation" => Former::validation()->NotEmpty()->LengthMoreThen(3)->Int()->LessThen(1450)
    ));

## Getting the form's markup or data

    $registerForm = Former::get("register-user", array("description" => "...", "job" => "front-end"));
    if($registerForm->submitted && $registerForm->success) {
        // Form is submitted
        $data = $registerForm->data;
        var_dump($data);
    } else {
        // The form is still not submitted or it doesn't pass the validations
        $markup = $registerForm->markup;
        echo $markup;
    }

## Adding default values:

    $registerForm = Former::get("register-user", (object) array(
        "description" => "text here ...", 
        "job" => "front-end"
    ));

## Changing the url

    $registerForm = Former::get("register-user");
    $registerForm->url("/new/url/here");

## Validation
The data in every of the controls could be validated. Just pass *validation* property along with the others.

    $form->addTextBox(array(
        "name" => "username", 
        "label" => "Your name:", 
        "validation" => Former::validation()->NotEmpty()
    ))

Chaining several validators:

    $form->addTextBox(array(
        "name" => "username", 
        "label" => "Your name:", 
        "validation" => Former::validation()->NotEmpty()->LengthMoreThen(5)->String()
    ))

Available validators:

    ->NotEmpty()
    ->LengthMoreThen(5)
    ->LengthLessThen(5)
    ->ValidEmail()
    ->Match("/^([a-zA-Z0-9])+$/")
    ->Not()
    ->MoreThen(600)
    ->LessThen(100)
    ->Int()
    ->Float()
    ->String()

## Custom html templates
If you need to change the html markup or just to add new logic copy the content of *tpl* directory in a new place. After that just set the new path like that:

    Former::templatesPath(__DIR__."/");

## CSS styles
The generated markup require some CSS to look good. It is available in *css* directory.

## Changing the error messages
The messages are available here:

    FormerValidation::$MESSAGE_NotEmpty = "Missing value.";
    FormerValidation::$MESSAGE_LengthMoreThen = "Wrong value length.";
    FormerValidation::$MESSAGE_LengthLessThen = "Wrong value length.";
    FormerValidation::$MESSAGE_Match = "Wrong value.";
    FormerValidation::$MESSAGE_Not = "Wrong value.";
    FormerValidation::$MESSAGE_ValidEmail = "Invalid email.";
    FormerValidation::$MESSAGE_MoreThen = "Wrong value.";
    FormerValidation::$MESSAGE_LessThen = "Wrong value.";
    FormerValidation::$MESSAGE_Int = "Wrong value.";
    FormerValidation::$MESSAGE_String = "Wrong value.";
    FormerValidation::$MESSAGE_Float = "Wrong value.";