import React from 'react'
import ReactDOM from 'react-dom'
import { ApolloClient, InMemoryCache, ApolloProvider } from '@apollo/client'
import * as Redux from 'redux'
import { composeWithDevTools } from 'redux-devtools-extension'
import createSagaMiddleware from 'redux-saga'

import App from 'src/App'
import { Store } from 'src/redux/store'
import { Provider } from 'react-redux'
import { appReducers } from 'src/redux/reducers'
import * as Actions from 'src/redux/actions'
import './index.scss'

const uri = `${window.location.protocol}//${window.location.hostname}:3200`
const client = new ApolloClient({
	uri,
	cache: new InMemoryCache(),
})

const sagaMiddleware = createSagaMiddleware()
const store: Redux.Store<Store> = Redux.createStore(
	appReducers,
	composeWithDevTools(Redux.applyMiddleware(sagaMiddleware)),
)

// check for local auth token
store.dispatch(Actions.verifyAuthStatus())

ReactDOM.render(
	<React.StrictMode>
		<Provider store={store}>
			<ApolloProvider client={client}>
				<App />
			</ApolloProvider>
		</Provider>
	</React.StrictMode>,
	document.getElementById('root'),
)
