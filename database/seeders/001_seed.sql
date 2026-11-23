USE thdh;

INSERT IGNORE INTO product_categories (id,name,slug,description,is_active,sort_order) VALUES
(1,'E-books & Publications','ebooks','Digital books and guides',1,1),
(2,'Websites & Templates','websites','Ready-to-use website packages',1,2),
(3,'Mobile Apps & Software','software','Software and application resources',1,3),
(4,'Courses & Training','courses','Learning and development resources',1,4),
(5,'Creative & Multimedia','creative','Design, video and audio resources',1,5);

INSERT IGNORE INTO services (id,name,slug,summary,description,icon,is_active,sort_order) VALUES
(1,'Web Development','web-development','Fast, responsive websites and web platforms.','Strategy, design and development for business websites, stores and web applications.','code',1,1),
(2,'App Development','app-development','Mobile and cross-platform application development.','Practical apps shaped around your users and workflows.','smartphone',1,2),
(3,'Digital Marketing','digital-marketing','Campaigns and content that reach the right audience.','Digital marketing plans, social content and campaign delivery.','megaphone',1,3),
(4,'Graphic Design','graphic-design','Professional visual identity and brand assets.','Logos, brand kits, flyers and social graphics.','pen-tool',1,4),
(5,'Video Editing','video-editing','Clean, effective editing for promos and events.','Pacing, sound and motion for engaging video content.','video',1,5),
(6,'Corporate Training','corporate-training','Live and self-paced training for teams.','Tailored digital skills and professional development programs.','users',1,6);
