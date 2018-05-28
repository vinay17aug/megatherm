# import libraries
from urllib.request import urlopen
from bs4 import BeautifulSoup
import re

# specify the url
quote_page = 'https://blog.rankingbyseo.com/'

# query the website and return the html to the variable 'page'
page = urlopen(quote_page)

# parse the html using beautiful soup and store in variable `soup`
soup = BeautifulSoup(page, 'html.parser')

links = []

for link in soup.findAll('a', attrs={'href': re.compile("^http://")}):
    links.append(link.get('href'))
    
    
print (links)