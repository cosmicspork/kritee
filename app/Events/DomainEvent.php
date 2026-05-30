<?php

namespace App\Events;

/**
 * Marker for events fired on the unit-of-work boundary.
 *
 * Concrete domain events are dispatched only from actions; the agent layer and
 * webhooks attach to this seam.
 */
interface DomainEvent {}
