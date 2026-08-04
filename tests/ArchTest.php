<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('services are final')
    ->expect('Daikazu\LaravelGlider\Support')
    ->classes()->toBeFinal();
