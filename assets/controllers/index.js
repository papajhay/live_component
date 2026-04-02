import { application } from '@symfony/stimulus-bridge';

//charge automatiquement tous les controllers (dont live)
application.registerControllers(
    require.context('./', true, /\.js$/)
);