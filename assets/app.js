/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

//------------------------------------------------------------------------------
// Registers Stimulus controllers from controllers.json and in the controllers/ directory
//------------------------------------------------------------------------------
import {startStimulusApp} from '@symfony/stimulus-bundle';
window.app = startStimulusApp();

//------------------------------------------------------------------------------
// Register 3rd party Controllers
//------------------------------------------------------------------------------
import LiveController from '@symfony/ux-live-component';
app.register('live', LiveController);

console.log('Welcome to Tasking Bundle Demo! 🎉');
