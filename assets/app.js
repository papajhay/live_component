//import './styles/app.css';
//import './stimulus_bootstrap.js';

import '@symfony/ux-live-component';
import '@symfony/ux-live-component/dist/live.min.css';

import { startStimulusApp } from '@symfony/stimulus-bridge';

//startStimulusApp(require.context('./controllers', true, /\.js$/));
//import { startStimulusApp } from '@symfony/stimulus-bundle';
//import { registerControllers } from '@symfony/ux-live-component';

const app = startStimulusApp();
registerControllers(app);   // important