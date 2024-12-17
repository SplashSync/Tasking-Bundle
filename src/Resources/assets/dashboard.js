/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
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
import '@symfony/ux-live-component/styles/live.css';
app.register('live', LiveController);

