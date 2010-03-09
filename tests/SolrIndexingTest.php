<?php
/**

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.
 
*/
 
class SolrIndexingTest extends SapphireTest
{
	static $fixture_file = 'solr/tests/SolrTest.yml';
	
	public function testSolrIndexableItem()
	{
		DataObject::add_extension('SiteTree', 'SolrIndexable');
		$item = $this->objFromFixture('Page','page1');
		
		$indexFields = array(
			'Title' => array(
				'Type' => 'Varchar', 
				'Value' => 'Page 1'
			),
			'Content' => array(
				'Type' => 'HTMLText',
				'Value' => 'Text content in the page',
			),
			'ID' => 1,
			'ClassName' => 'Page'
		);
		// call onAfterPublish, we expect the 
		$searchService = $this->getMock('SolrSearchService', array('index'));
		$searchService->expects($this->once())
			->method('index');
			// ->with($this->equalTo($indexFields));
			
		global $_SINGLETONS;
		$_SINGLETONS['SolrSearchService'] = $searchService;
		
		// now save the item and hope it gets indexed
		$item->extend('onAfterPublish', $item);
	}

	public function testStoreIndexAndQuery()
	{
		global $_SINGLETONS;
		$_SINGLETONS['SolrSearchService'] = new SolrSearchService();
		// we should be able to perform a query for documents with 'text content'
		// and receive back some valid data
		$search = singleton('SolrSearchService');
		// clear everything out, then index content

		/* @var $search SolrSearchService */
		$search->getSolr()->deleteByQuery('*:*');
		
		$item = $this->objFromFixture('Page','page1');
		$item->extend('onAfterPublish', $item);
		$results = $search->queryLucene('content_t:"Text Content"');
		
		$this->assertNotNull($results->docs);
		$this->assertEquals(1, count($results->docs));
		$this->assertEquals('Page_1', $results->docs[0]->id);
		
	}

	public function testQueryForObjects()
	{
	    global $_SINGLETONS;
		$_SINGLETONS['SolrSearchService'] = new SolrSearchService();
		// we should be able to perform a query for documents with 'text content'
		// and receive back some valid data
		$search = singleton('SolrSearchService');
		// clear everything out, then index content
		/* @var $search SolrSearchService */
		$search->getSolr()->deleteByQuery('*:*');

		$item = $this->objFromFixture('Page','page1');
		$item->extend('onAfterPublish', $item);
		$results = $search->queryDataObjects('content_t:"Text Content"');

		$this->assertTrue($results instanceof DataObjectSet);
		$this->assertEquals(1, $results->Count());
		$items = $results->toArray();
		$this->assertEquals('Page', $items[0]->ClassName);
		$this->assertEquals(1, $items[0]->ID);
	}

	public function testFacetQuery()
	{
		global $_SINGLETONS;
		$_SINGLETONS['SolrSearchService'] = new SolrSearchService();
		// we should be able to perform a query for documents with 'text content'
		// and receive back some valid data
		$search = singleton('SolrSearchService');
		// clear everything out, then index content

		/* @var $search SolrSearchService */
		$search->getSolr()->deleteByQuery('*:*');
		
		$item = $this->objFromFixture('Page','page1');
		$item->extend('onAfterPublish', $item);
		
		$item = $this->objFromFixture('Page','page2');
		$item->extend('onAfterPublish', $item);
		
		$item = $this->objFromFixture('Page','page3');
		$item->extend('onAfterPublish', $item);
		
		$item = $this->objFromFixture('Page','page4');
		$item->extend('onAfterPublish', $item);
		
		$results = $search->queryLucene('content_t:"Text Content"', 1, 10, array('facet'=>'true', 'facet.field' => 'content_t'));
		
		$this->assertNotNull($results->docs);
		$this->assertEquals(4, count($results->docs));
		$this->assertEquals('Page_1', $results->docs[0]->id);
		
		// print_r($results);
	}
}


?>