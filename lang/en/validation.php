<?php

/*
 | Friendly overrides of the framework messages. Only the keys listed here
 | change; everything else keeps the framework wording. ":Attribute" is the
 | field label with a capital first letter, so "name" reads "Name is required."
 */
return [
    'required' => ':Attribute is required.',
    'required_with' => ':Attribute is required.',
    'required_if' => ':Attribute is required.',
    'filled' => ':Attribute is required.',
    'email' => 'Enter a valid email, like name@school.com.',
    'unique' => 'This :attribute is already used.',
    'exists' => 'Pick a valid :attribute from the list.',
    'numeric' => ':Attribute must be a number.',
    'integer' => ':Attribute must be a whole number.',
    'date' => 'Enter a valid :attribute.',
    'boolean' => ':Attribute must be yes or no.',
    'image' => ':Attribute must be an image.',
    'array' => ':Attribute is not valid.',
    'string' => ':Attribute must be text.',
    'confirmed' => ':Attribute does not match.',
    'regex' => ':Attribute is not in the right format.',
    'in' => 'Pick a valid :attribute from the list.',
    'min' => [
        'numeric' => ':Attribute cannot be less than :min.',
        'string' => ':Attribute must be at least :min characters.',
        'array' => 'Pick at least :min :attribute.',
        'file' => ':Attribute must be at least :min KB.',
    ],
    'max' => [
        'numeric' => ':Attribute cannot be more than :max.',
        'string' => ':Attribute can be at most :max characters.',
        'array' => 'Pick at most :max :attribute.',
        'file' => ':Attribute must be smaller than :max KB.',
    ],
    'gte' => [
        'numeric' => ':Attribute must be at least :value.',
    ],
    'after' => ':Attribute must be after :date.',
    'after_or_equal' => ':Attribute cannot be before :date.',
    'before' => ':Attribute must be before :date.',
    'before_or_equal' => ':Attribute cannot be after :date.',
    'mimes' => ':Attribute must be a file of type: :values.',
];
