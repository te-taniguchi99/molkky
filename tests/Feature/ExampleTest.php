<?php

test('the top page redirects to the main screen', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});
