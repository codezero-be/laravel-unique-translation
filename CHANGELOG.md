# Changelog

All Notable changes to `UniqueTranslation` will be documented in this file.

## 3.1.0 (2020-03-03)

- Add support for Laravel 7

## 3.0.0 (2020-01-22)

- Show only one error message per validated attribute (#5)

## 2.0.0 (2019-10-11)

- Upgrade dependencies to support Laravel 6

## 1.2.0 (2019-05-01)

- Enable the use of extra `where` clauses

## 1.1.1 (2018-10-20)

- Allow adding a database connection to the rule

## 1.1.0 (2017-10-27)

There has been a lot of refactoring and a few features were added, but none of these should be breaking changes:

-   Refactor and merge tests
-   Rewrite Validator extension
-   Update Validation Rule to return a string representation of the rule
-   Always return an error for both the attribute name (`$errors->first('slug')`) and the localized attribute name (`$errors->first('slug.en')`) when validation fails
-   Handle custom error messages
-   Add name Rule constructor: `UniqueTranslationRule::for($table)`

## 1.0.0 (2017-10-22)

- Version 1.0.0 of `UniqueTranslation`
