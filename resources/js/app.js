import './bootstrap';

import Alpine from 'alpinejs';
import { hubungkanAlpine, pasangSemua } from './select2';

window.Alpine = Alpine;

hubungkanAlpine(Alpine);
Alpine.start();

// Pilihan di luar komponen Alpine (mis. formulir filter biasa).
pasangSemua();
