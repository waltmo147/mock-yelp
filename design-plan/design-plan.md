# Project 2, Milestone 1 - Design & Plan

Your Name: Yufu Mo
(ym445)

## 1. Persona

I've selected **[Abby]** as my persona.

I've selected my persona because:

1) Abby has low confidence about doing unfamiliar computing tasks. I think even if she cannot figure out how this website woks, she will still blame herself first.

2) Abby might be willing to spend time figuring out how the website works.

## 2.Describe your Catalog

[What will your collection be about? What types of attributes (database columns) will you keep track of for the *things* in your collection? 1-2 sentences.]

My collection is restaurants' information.

Fields: name, picture, location, phone number, rating, price, tags(type).

## 3. Sketch & Wireframe

[Insert your 1 sketch here.]
![sketch](/images/sketch1.png)
[Insert your 1 wireframe here.]
![wireframe](/images/wireframe1.png)
[Explain why your design would be effective for your persona. 1-3 sentences.]

1) Because my sketches are based on pretty simple design, which is good for Abby since she rarely has spare time to learn how to use the website.

2) It is only one page, and with not much information. It should be easy to use.

## 4. Database Schema Design

[Describe the structure of your database. You may use words or a picture. A bulleted list is probably the simplest way to do this.]

Table: restaurants
* id: INTEGER, not null, PK, AI, U
* name: TEXT, not null
* image_address: TEXT, U
* price: INTEGER
* location1: TEXT, not null
* location2: TEXT, not null
* phone: TEXT, not null
* rating: REAL
* tags: TEXT


## 5. Database Query Plan

[Plan your database queries. You may use natural language, pseudocode, or SQL.]

1. All records
```sql
SELECT * FROM restaurants;
```

2. Search records by user selected field
```sql
SELECT tags FROM restaurants WHERE tags LIKE '%pizza%';
```

3. Insert record
```sql
INSERT INTO restaurants (...) values (...);
```

## 6. *Filter Input, Escape Output* Plan

[Describe your plan for filtering the input from your HTML forms. Describe your plan for escaping any values that you use in HTML or SQL. You may use natural language and/or pseudocode.]

1. Filter Input:
   The user might input name, price, location, phone, rating, tags for the restaurant. They might also input an image. For name, location, phone, tags, these should be TEXT, which means in PHP code they are strings. To filter these strings, I will first sanitize them. And according to what structure they are supposed to be, I will filter out the wrong input. For tags, I will separate the words and filter them individually. For price, it should be filtered as integer and I also need to make sure it is non-negative. For rating, it should be filtered as float and I also need to make sure it is in 0 to 5. Images should be chosen from the local computer.

2. Escape Output:
   For name, location, phone, tags, to filter these strings, I will first sanitize them again. And then I will filter out the wrong input. For tags, I will separate the words and filter them individually. For price, it should be filtered as integer and I also need to make sure it is non-negative. For rating, it should be filtered as float and I also need to make sure it is in 0 to 5.


## 7. Additional Code Planning

[If you need more code planning that didn't fit into the above sections, put it here.]
