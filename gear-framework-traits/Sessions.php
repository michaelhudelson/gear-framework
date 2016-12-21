<?php
////////////////////////////////////////////////////////////////////////////////
//
// SESSIONS
//
////////////////////////////////////////////////////////////////////////////////

namespace GearFramework;

trait Sessions
{
    // start a session if not started
    public function startSession()
    {
        if (!session_id()) {
            session_start();
        }
    }

    // destroy session
    public function endSession()
    {
        session_regenerate_id(true);
        session_destroy();
    }
}
