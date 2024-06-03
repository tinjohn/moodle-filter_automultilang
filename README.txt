To Install it:
    - Enable if from "Administration/Filters".

To Use it:
    - Add an Translation API Key
    - Test it (by changing your language).
    - Use it after h5p_filter

How it works:
    - gets the content
    - checks for existing translation in database for current language and returns the translation
    - if not existent, creates a translation and puts it to the database and returns the translation
