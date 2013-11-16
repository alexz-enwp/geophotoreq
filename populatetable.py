#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright 2013 Alex Zaddach. (mrzmanwiki@gmail.com)

# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

import MySQLdb
import os
import sys

class GeoPhotoReqGen(object):

	def __init__(self):
		self.db = MySQLdb.connect(host="enwiki.labsdb", read_default_file="/data/project/geophotoreq/replica.my.cnf")
		self.cursor = self.db.cursor()
		self.catlist = set()

	def run(self):
		self.getCatLists()
		self.getRequestedTitles()
		self.getNoImages()
		self.getNoJPGs()
		self.repopulateMainTable()

	def getCatLists(self):
		query = 'SELECT page_title FROM enwiki_p.page INNER JOIN enwiki_p.categorylinks ON cl_from=page_id WHERE page_namespace=14 AND cl_to=%s'
		catstodo = ['Wikipedia_requested_photographs']
		catsdone = []
		self.catlist.add(catstodo[0])
		while True:
			try:
				cat = catstodo.pop()
			except:
				break
			rows = self.cursor.execute(query, (cat))
			catsdone.append(cat)
			if rows == 0:
				continue
			cats = self.cursor.fetchall()
			for c in cats:
				if not c[0] in catsdone and not c[0] in catstodo and not 'requested_map' in c[0].lower():
					catstodo.append(c[0])
					self.catlist.add(c[0])
			if len( catstodo) == 0:
				break
				
	def getRequestedTitles(self):
		self.cursor.execute("""CREATE TABLE IF NOT EXISTS p50380g50838__geophotoreq.photo_tmp (
		`title` varchar(255) NOT NULL,
		`coordinate` point NOT NULL,
		`reqphoto` tinyint(1) DEFAULT '0',
		`noimg` tinyint(1) DEFAULT '0',
		`nojpg` tinyint(1) DEFAULT '0',
		UNIQUE KEY `title` (`title`)
		) ENGINE=MyISAM""")
		self.cursor.execute("TRUNCATE TABLE p50380g50838__geophotoreq.photo_tmp")
		query = """INSERT IGNORE INTO p50380g50838__geophotoreq.photo_tmp (title, coordinate, reqphoto)
		SELECT page2.page_title, POINT(gt_lat, gt_lon), 1 FROM enwiki_p.page AS page1 
		JOIN enwiki_p.categorylinks ON page1.page_id=cl_from 
		JOIN enwiki_p.page as page2 ON page2.page_title=page1.page_title AND page2.page_namespace=0 
		JOIN enwiki_p.geo_tags ON gt_page_id=page2.page_id AND gt_primary=1 AND gt_globe='earth' 
		WHERE page1.page_namespace=1 AND cl_to=%s"""
		for cat in self.catlist:
			self.cursor.execute(query, (cat))
			
	def getNoImages(self):
		self.cursor.execute("""INSERT INTO p50380g50838__geophotoreq.photo_tmp (title, coordinate, noimg)
		SELECT page_title, POINT(gt_lat, gt_lon), 1 FROM enwiki_p.page
		JOIN enwiki_p.geo_tags ON gt_page_id=page_id AND gt_primary=1 AND gt_globe='earth' 
		LEFT JOIN enwiki_p.imagelinks ON page_id=il_from
		WHERE page_namespace=0 AND page_is_redirect=0 AND il_to IS NULL
		ON DUPLICATE KEY UPDATE noimg=1""")
		
	def getNoJPGs(self):
		self.cursor.execute("""INSERT INTO p50380g50838__geophotoreq.photo_tmp (title, coordinate, nojpg)
		SELECT page_title, POINT(gt_lat, gt_lon), 1 FROM enwiki_p.page
		JOIN enwiki_p.geo_tags ON gt_page_id=page_id AND gt_primary=1 AND gt_globe='earth' 
		LEFT JOIN enwiki_p.imagelinks ON page_id=il_from 
		AND (CONVERT(il_to USING latin1) COLLATE latin1_swedish_ci LIKE "%.jpg" OR CONVERT(il_to USING latin1) COLLATE latin1_swedish_ci LIKE "%.jpeg")
		WHERE page_namespace=0 AND page_is_redirect=0 AND il_to IS NULL
		ON DUPLICATE KEY UPDATE nojpg=1""")

	def repopulateMainTable(self):	
		self.cursor.execute("""CREATE TABLE IF NOT EXISTS p50380g50838__geophotoreq.photocoords (
		`title` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
		`coordinate` point NOT NULL,
		`reqphoto` tinyint(1) DEFAULT '0',
		`noimg` tinyint(1) DEFAULT '0',
		`nojpg` tinyint(1) DEFAULT '0',
		SPATIAL KEY `coordinate` (`coordinate`)
		) ENGINE=MyISAM""")
		self.cursor.execute("LOCK TABLES p50380g50838__geophotoreq.photocoords WRITE, p50380g50838__geophotoreq.photo_tmp WRITE")
		self.cursor.execute("TRUNCATE TABLE p50380g50838__geophotoreq.photocoords")
		self.cursor.execute("INSERT INTO p50380g50838__geophotoreq.photocoords SELECT * FROM p50380g50838__geophotoreq.photo_tmp")
		self.cursor.execute("UNLOCK TABLES")
		self.cursor.execute("DROP TABLE p50380g50838__geophotoreq.photo_tmp")

if __name__ == "__main__":
	g = GeoPhotoReqGen()
	g.run()
