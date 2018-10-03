---
layout: page
title: Guides
permalink: /guides/
---

> Heads up! These Guides cover the previous version 2.x of Spout. Even though some things have changed in version 3.x - the concepts in these guides can still be beneficial. Please contribute and write new guides and recipes for Spout.

These guides focus on common and more advanced usages of {{ site.spout_html }}.<br>
If you are just starting with {{ site.spout_html }}, check out the [Getting Started page]({{ site.github.url }}/getting-started/) and the [Documentation]({{ site.github.url }}/docs/) first.

{% assign pages=site.pages | sort: 'path' %}
<ul>
{% for page in pages %}
  {% if page.title and page.category contains 'guide' %}
  <li>
    <a class="page-link" href="{{ page.url | prepend: site.github.url }}">{{ page.title }}</a>
  </li>
  {% endif %}
{% endfor %}
</ul>
