<html>
	<head>
	</head>
	<body>
		<!-- BEGIN one -->
			<div id="one">{one.var}
				<!-- BEGIN two_a -->
					<div id="two_a">
						<!-- BEGIN three_a -->
							<div id="three_a">Var: {one.two_a.three_a.var}</div>
							<!-- BEGIN four_a -->
							<div id="four_a">Var: {one.two_a.three_a.four_a.var} Parentvar: {one.two_a.three_a.var}</div>
							<!-- END four_a -->
							<!-- BEGIN four_a -->
							<div id="four_a2">{one.two_a.three_a.four_a.var}</div>
							<!-- END four_a -->
						<!-- END three_a -->
						<!-- BEGIN three_b -->
							<div id="three_b">{one.two_a.three_b.var}</div>
						<!-- END three_b -->
						<!-- BEGIN three_d -->
							<div id="three_d">{one.two_a.three_d.var}</div>
						<!-- END three_d -->
					</div>
				<!-- END two_a -->
				<!-- BEGIN two_b -->
					<div id="two_b">
						<!-- BEGIN three_c -->
							<div id="three_c">{one.two_b.three_c.var}</div>
						<!-- END three_c -->
					</div>
				<!-- END two_b -->
			</div>
		<!-- END one -->
	</body>
</html>