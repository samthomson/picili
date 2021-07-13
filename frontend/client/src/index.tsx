import React from 'react'
import ReactDOM from 'react-dom'
import './index.scss'
import { ApolloClient, InMemoryCache, ApolloProvider } from '@apollo/client'
import App from 'src/App'
// import reportWebVitals from './reportWebVitals'

const uri = `${window.location.protocol}//${window.location.hostname}:3200`
const client = new ApolloClient({
	uri,
	cache: new InMemoryCache(),
})

ReactDOM.render(
	<React.StrictMode>
		<ApolloProvider client={client}>
			<App />
		</ApolloProvider>
	</React.StrictMode>,
	document.getElementById('root'),
)

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
// reportWebVitals()
