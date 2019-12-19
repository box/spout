---
layout: page
title: Guides
permalink: /guides/
---

{% include set-global-site-url.html %}

These guides focus on common and more advanced usages of {{ site.spout_html }}.<br>
If you are just starting with {{ site.spout_html }}, check out the [Getting Started page]({{ site_url }}/getting-started/) and the [Documentation]({{ site_url }}/docs/) first.

{% assign pages=site.pages | sort: 'path' %}
<ul>
{% for page in pages %}
  {% if page.title and page.category contains 'guide' %}
  <li>
    <a class="page-link" href="{{ page.url | prepend: site_url }}">{{ page.title }}</a>
  </li>
  {% endif %}
{% endfor %}
</ul>
