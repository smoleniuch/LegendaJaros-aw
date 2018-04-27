import ReduxThunk from 'redux-thunk';

import http from './http'
import loadingBar from './loading_bar'
import router from './router'

export default [

    
  ReduxThunk,
  http(),
  router,
  loadingBar(),

]
