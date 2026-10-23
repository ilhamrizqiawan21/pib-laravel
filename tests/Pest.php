<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Feature: bootstrap Laravel + database bersih tiap tes. Unit/Domain butuh config() saja.
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');
