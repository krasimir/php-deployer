# Super simple template engine

- - -

## Initialization

    View::$root = [path to your templates];

## Usage

    $htmlContent = view([path to template], [associative array with variables]);

## Example

    $htmlContent = view("/tpl/home.html", array(
        "titleOfThePage" => "Fabrico test",
        "title" => "Fabrico",
        "content" => "It works!"
    ));

## If you want to pass variables to every of the views:

    View::$forEachView = array(
        "globalVar" => "global variable"
    );