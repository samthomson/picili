import React from 'react'
import ReactDOM from 'react-dom'
import {
	ApolloClient,
	InMemoryCache,
	ApolloProvider,
	createHttpLink,
	DefaultOptions,
} from '@apollo/client'
import * as Redux from 'redux'
import { composeWithDevTools } from 'redux-devtools-extension'
import createSagaMiddleware from 'redux-saga'

import CheckToken from 'src/components/structure/CheckToken'
import AppRouter from 'src/components/structure/AppRouter'
import { Store } from 'src/redux/store'
import { Provider } from 'react-redux'
import { appReducers } from 'src/redux/reducers'
import './index.scss'
import 'semantic-ui-css/semantic.min.css'

const uri = `${window.location.protocol}//${window.location.hostname}:3200/graphql`

const link = createHttpLink({
	uri,
	credentials: 'include',
})
const defaultOptions: DefaultOptions = {
	watchQuery: {
		fetchPolicy: 'no-cache',
		errorPolicy: 'ignore',
	},
	query: {
		fetchPolicy: 'no-cache',
		errorPolicy: 'all',
	},
}

const client = new ApolloClient({
	cache: new InMemoryCache(),
	link,
	defaultOptions,
})

const sagaMiddleware = createSagaMiddleware()
const store: Redux.Store<Store> = Redux.createStore(
	appReducers,
	composeWithDevTools(Redux.applyMiddleware(sagaMiddleware)),
)

ReactDOM.render(
	<React.StrictMode>
		<Provider store={store}>
			<ApolloProvider client={client}>
				<CheckToken />
				<AppRouter />
			</ApolloProvider>
		</Provider>
	</React.StrictMode>,
	document.getElementById('root'),
)
